<?php

namespace App\Services;

use App\Models\Product;

/**
 * Calculates a product's final price for a given set of answers, by
 * starting at the base price, adding each selected option's own price
 * modifier, then applying rules on top.
 *
 * Two entry points, same underlying calculation:
 *   - calculate() reads the product's live/draft data straight from the
 *     database (used by the admin side — e.g. previewing a price).
 *   - calculateFromSnapshot() reads a product's frozen published_snapshot
 *     instead (used by the public quote builder), so a business editing
 *     their product never changes what customers see mid-edit — see
 *     App\Models\Product::buildPublishableSnapshot().
 *
 * See docs/rules-engine.md for the condition_logic / action_logic JSON
 * format, with worked examples.
 */
class RulesEngine
{
    /**
     * @param  int  $productId
     * @param  array<int, mixed>  $answers  question_id => option_id (single_choice) or a raw value (number/text)
     * @return array{base_price: float, final_price: float, applied_rules: array}
     */
    public function calculate(int $productId, array $answers): array
    {
        $product = Product::with(['rules', 'questions.options'])->findOrFail($productId);

        $questions = $product->questions->map(fn ($question) => [
            'id' => $question->id,
            'type' => $question->type,
            'options' => $question->options->map(fn ($option) => [
                'id' => $option->id,
                'price_modifier' => (float) $option->price_modifier,
            ])->all(),
        ])->all();

        $rules = $product->rules->map(fn ($rule) => [
            'id' => $rule->id,
            'name' => $rule->name,
            'condition_logic' => $rule->condition_logic,
            'action_logic' => $rule->action_logic,
        ])->all();

        return $this->computeFromNormalized((float) $product->base_price, $questions, $rules, $answers);
    }

    /**
     * Same calculation, but sourced from a product's frozen published
     * snapshot (see Product::buildPublishableSnapshot()) instead of the
     * live database rows.
     *
     * @param  array<int, mixed>  $answers
     * @return array{base_price: float, final_price: float, applied_rules: array}
     */
    public function calculateFromSnapshot(array $snapshot, array $answers): array
    {
        $basePrice = (float) ($snapshot['product']['base_price'] ?? 0);

        $questions = collect($snapshot['questions'] ?? [])->map(fn ($question) => [
            'id' => $question['id'],
            'type' => $question['type'],
            'options' => $question['options'] ?? [],
        ])->all();

        $rules = $snapshot['rules'] ?? [];

        return $this->computeFromNormalized($basePrice, $questions, $rules, $answers);
    }

    /**
     * @param  array<int, array{id: int, type: string, options: array}>  $questions
     * @param  array<int, array{id: int, name: string, condition_logic: mixed, action_logic: mixed}>  $rules
     * @param  array<int, mixed>  $answers
     * @return array{base_price: float, final_price: float, applied_rules: array}
     */
    private function computeFromNormalized(float $basePrice, array $questions, array $rules, array $answers): array
    {
        $runningTotal = $basePrice;
        $optionContributions = [];

        foreach ($questions as $question) {
            if ($question['type'] !== 'single_choice' || ! array_key_exists($question['id'], $answers)) {
                continue;
            }

            $selectedOptionId = $answers[$question['id']];
            $option = collect($question['options'])->first(fn ($o) => (string) $o['id'] === (string) $selectedOptionId);

            if ($option) {
                $amount = (float) $option['price_modifier'];
                $optionContributions[$option['id']] = $amount;
                $runningTotal += $amount;
            }
        }

        $appliedRules = [];

        foreach ($rules as $rule) {
            if (! $this->conditionsMatch($rule['condition_logic'] ?? null, $answers)) {
                continue;
            }

            $before = $runningTotal;

            $this->applyAction($rule['action_logic'] ?? [], $runningTotal, $optionContributions);

            $appliedRules[] = [
                'rule_id' => $rule['id'],
                'name' => $rule['name'],
                'action' => $rule['action_logic'],
                'amount_changed' => round($runningTotal - $before, 2),
            ];
        }

        return [
            'base_price' => round($basePrice, 2),
            'final_price' => round($runningTotal, 2),
            'applied_rules' => $appliedRules,
        ];
    }

    /**
     * A rule's condition_logic is either a single condition object, or an
     * array of condition objects that must ALL match (AND).
     */
    private function conditionsMatch(mixed $conditionLogic, array $answers): bool
    {
        if (empty($conditionLogic)) {
            return false;
        }

        if (array_is_list($conditionLogic)) {
            foreach ($conditionLogic as $condition) {
                if (! $this->conditionMatches($condition, $answers)) {
                    return false;
                }
            }

            return true;
        }

        return $this->conditionMatches($conditionLogic, $answers);
    }

    private function conditionMatches(array $condition, array $answers): bool
    {
        $questionId = $condition['question_id'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $expected = $condition['value'] ?? null;

        if ($questionId === null || ! array_key_exists($questionId, $answers)) {
            return false;
        }

        $actual = $answers[$questionId];

        return match ($operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'greater_than' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            'less_than' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            'greater_than_or_equal' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            'less_than_or_equal' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            default => false,
        };
    }

    /**
     * A rule's action_logic has a "type" of add_fixed, add_percentage, or
     * set_option_price. Mutates $runningTotal and $optionContributions.
     */
    private function applyAction(array $action, float &$runningTotal, array &$optionContributions): void
    {
        switch ($action['type'] ?? null) {
            case 'add_fixed':
                $runningTotal += (float) ($action['amount'] ?? 0);
                break;

            case 'add_percentage':
                $runningTotal += $runningTotal * ((float) ($action['percent'] ?? 0) / 100);
                break;

            case 'set_option_price':
                $optionId = $action['option_id'] ?? null;

                if ($optionId !== null && array_key_exists($optionId, $optionContributions)) {
                    $newAmount = (float) ($action['price_modifier'] ?? 0);
                    $runningTotal += $newAmount - $optionContributions[$optionId];
                    $optionContributions[$optionId] = $newAmount;
                }

                break;
        }
    }
}
