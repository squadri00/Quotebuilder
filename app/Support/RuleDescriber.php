<?php

namespace App\Support;

use App\Models\Option;
use App\Models\Question;
use App\Models\Rule;

/**
 * Turns a Rule's condition_logic / action_logic JSON into a plain-English
 * sentence for display, e.g. "If Paper Type = Glossy, add $20."
 */
class RuleDescriber
{
    public static function describe(Rule $rule): string
    {
        return sprintf(
            'If %s, %s.',
            static::describeCondition($rule->condition_logic),
            static::describeAction($rule->action_logic)
        );
    }

    private static function describeCondition(mixed $conditionLogic): string
    {
        if (empty($conditionLogic)) {
            return 'no condition is set';
        }

        if (array_is_list($conditionLogic)) {
            return collect($conditionLogic)
                ->map(fn ($condition) => static::describeSingleCondition($condition))
                ->implode(' AND ');
        }

        return static::describeSingleCondition($conditionLogic);
    }

    private static function describeSingleCondition(array $condition): string
    {
        $question = Question::find($condition['question_id'] ?? null);
        $value = $condition['value'] ?? null;

        $questionLabel = $question?->question_text ?? 'an unknown question';
        $operatorLabel = static::operatorLabel($condition['operator'] ?? 'equals');
        $valueLabel = static::valueLabel($question, $value);

        return "{$questionLabel} {$operatorLabel} {$valueLabel}";
    }

    private static function operatorLabel(string $operator): string
    {
        return match ($operator) {
            'equals' => '=',
            'not_equals' => '≠',
            'greater_than' => '>',
            'less_than' => '<',
            'greater_than_or_equal' => '≥',
            'less_than_or_equal' => '≤',
            default => $operator,
        };
    }

    private static function valueLabel(?Question $question, mixed $value): string
    {
        if ($question?->type === 'single_choice') {
            $option = Option::find($value);

            return $option?->label ?? "option #{$value}";
        }

        return (string) $value;
    }

    private static function describeAction(mixed $actionLogic): string
    {
        if (empty($actionLogic)) {
            return 'do nothing';
        }

        return match ($actionLogic['type'] ?? null) {
            'add_fixed' => 'add $'.number_format((float) ($actionLogic['amount'] ?? 0), 2),
            'add_percentage' => 'add '.static::trimZeros((float) ($actionLogic['percent'] ?? 0)).'%',
            'set_option_price' => static::describeSetOptionPrice($actionLogic),
            default => 'do something unrecognized',
        };
    }

    private static function describeSetOptionPrice(array $actionLogic): string
    {
        $option = Option::find($actionLogic['option_id'] ?? null);
        $optionLabel = $option?->label ?? 'an option';
        $price = number_format((float) ($actionLogic['price_modifier'] ?? 0), 2);

        return "set {$optionLabel}'s price to \${$price}";
    }

    private static function trimZeros(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2), '0'), '.');
    }
}
