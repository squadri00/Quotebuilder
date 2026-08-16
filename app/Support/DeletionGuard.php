<?php

namespace App\Support;

use App\Models\Option;
use App\Models\Question;
use App\Models\Rule;

/**
 * Catches a broken-link deletion at the moment it happens, instead of
 * only at Publish time (QuestionVisibility::assertPublishable covers
 * that later, harder-to-diagnose case). A question's display_conditions
 * and a rule's condition_logic/action_logic can reference another
 * question or option by ID — deleting the thing they point at leaves a
 * dangling reference that silently blocks publishing until someone
 * finds and fixes it. Blocking the delete up front, with a plain-English
 * reason, is easier to act on when it's fresh in mind.
 */
class DeletionGuard
{
    /**
     * "a" / "a and b" / "a, b, and c" — an implode(' and ', ...) reads as
     * a run-on once there's more than two blockers, which happens easily
     * (one question can be depended on by several others at once).
     */
    public static function joinList(array $items): string
    {
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        if (count($items) === 2) {
            return "{$items[0]} and {$items[1]}";
        }

        $last = array_pop($items);

        return implode(', ', $items).', and '.$last;
    }

    /**
     * @return array<int, string> empty means safe to delete
     */
    public static function blockersForQuestion(Question $question): array
    {
        $blockers = [];

        $otherQuestions = Question::where('product_id', $question->product_id)
            ->where('id', '!=', $question->id)
            ->get();

        foreach ($otherQuestions as $other) {
            foreach (self::conditionsIn($other->display_conditions) as $condition) {
                if ((int) ($condition['question_id'] ?? 0) === $question->id) {
                    $blockers[] = "the question \"{$other->question_text}\", which only shows based on this one";

                    break;
                }
            }
        }

        foreach (self::rulesFor($question->product_id) as $rule) {
            if (self::conditionReferencesQuestion($rule->condition_logic, $question->id)) {
                $blockers[] = "the rule \"{$rule->name}\", which checks this question";
            }
        }

        return array_values(array_unique($blockers));
    }

    /**
     * @return array<int, string> empty means safe to delete
     */
    public static function blockersForOption(Option $option): array
    {
        $blockers = [];
        $productId = $option->question->product_id;

        $questions = Question::where('product_id', $productId)->get();

        foreach ($questions as $question) {
            foreach (self::conditionsIn($question->display_conditions) as $condition) {
                if (self::valuesInclude($condition['value'] ?? null, $option->id)) {
                    $blockers[] = "the question \"{$question->question_text}\", which only shows based on this answer";

                    break;
                }
            }
        }

        foreach (self::rulesFor($productId) as $rule) {
            if (self::conditionReferencesOption($rule->condition_logic, $option->id)) {
                $blockers[] = "the rule \"{$rule->name}\", which checks this answer";
            }

            $action = $rule->action_logic ?? [];

            if (($action['type'] ?? null) === 'set_option_price' && (int) ($action['option_id'] ?? 0) === $option->id) {
                $blockers[] = "the rule \"{$rule->name}\", which sets this answer's price";
            }
        }

        return array_values(array_unique($blockers));
    }

    private static function rulesFor(int $productId)
    {
        return Rule::where('product_id', $productId)->get();
    }

    private static function conditionReferencesQuestion(mixed $conditionLogic, int $questionId): bool
    {
        foreach (self::conditionsIn($conditionLogic) as $condition) {
            if ((int) ($condition['question_id'] ?? 0) === $questionId) {
                return true;
            }
        }

        return false;
    }

    private static function conditionReferencesOption(mixed $conditionLogic, int $optionId): bool
    {
        foreach (self::conditionsIn($conditionLogic) as $condition) {
            if (self::valuesInclude($condition['value'] ?? null, $optionId)) {
                return true;
            }
        }

        return false;
    }

    private static function valuesInclude(mixed $value, int $optionId): bool
    {
        $values = is_array($value) ? $value : [$value];

        return in_array((string) $optionId, array_map('strval', $values), true);
    }

    /**
     * display_conditions is always {conditions: [...]}. A rule's
     * condition_logic is either one condition object or a list of them
     * (see RulesEngine::conditionsMatch / TemplateCloner::remapConditionLogic
     * for the same distinction made elsewhere).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function conditionsIn(mixed $logic): array
    {
        if (empty($logic)) {
            return [];
        }

        if (isset($logic['conditions'])) {
            return $logic['conditions'] ?? [];
        }

        return array_is_list($logic) ? $logic : [$logic];
    }
}
