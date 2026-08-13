<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Evaluates a question's display_conditions against a set of answers.
 *
 * A question's display_conditions is either empty/null (always show) or:
 *   {"logic": "and"|"or", "conditions": [{"question_id", "operator", "value"}, ...]}
 *
 * Supported operators: equals, not_equals, in (value is an array — matches
 * if the answer is any one of them; only meaningful for single_choice
 * questions, where value/answer are option IDs).
 *
 * This mirrors the equivalent JS in public/quote-builder.blade.php almost
 * line for line — the wizard needs instant client-side evaluation as the
 * customer answers each question (no round trip per step), so the same
 * logic necessarily exists twice. Keep both in sync if this changes.
 */
class QuestionVisibility
{
    public static function isVisible(mixed $displayConditions, array $answers): bool
    {
        $conditions = $displayConditions['conditions'] ?? [];

        if (empty($conditions)) {
            return true;
        }

        $logic = $displayConditions['logic'] ?? 'and';

        $results = array_map(
            fn (array $condition) => static::conditionMatches($condition, $answers),
            $conditions
        );

        return $logic === 'or' ? in_array(true, $results, true) : ! in_array(false, $results, true);
    }

    private static function conditionMatches(array $condition, array $answers): bool
    {
        $questionId = $condition['question_id'] ?? null;

        if ($questionId === null || ! array_key_exists($questionId, $answers)) {
            return false;
        }

        $actual = $answers[$questionId];
        $expected = $condition['value'] ?? null;

        return match ($condition['operator'] ?? 'equals') {
            'equals' => (string) $actual === (string) $expected,
            'not_equals' => (string) $actual !== (string) $expected,
            'in' => is_array($expected) && in_array((string) $actual, array_map('strval', $expected), true),
            default => false,
        };
    }

    /**
     * Walks $questions in order (must already be sorted by sort_order),
     * building up the answer set one question at a time so each
     * question's own display_conditions is judged only against answers
     * to questions that were themselves genuinely visible — never a
     * forward reference, never a value the customer couldn't actually
     * have seen or provided.
     *
     * This is what makes it safe to re-run server-side on a submitted
     * payload: a tampered answer to a question that should have been
     * hidden (given the customer's OTHER, legitimate answers) is simply
     * dropped here, never reaching the price calculation or getting
     * persisted as a QuoteAnswer.
     *
     * @param  array<int, array{id: int, display_conditions: mixed}>  $questions
     * @param  array<int, mixed>  $answers
     * @return array<int, mixed>
     */
    public static function filterAnswers(array $questions, array $answers): array
    {
        $visibleAnswers = [];

        foreach ($questions as $question) {
            if (! static::isVisible($question['display_conditions'] ?? null, $visibleAnswers)) {
                continue;
            }

            if (array_key_exists($question['id'], $answers)) {
                $visibleAnswers[$question['id']] = $answers[$question['id']];
            }
        }

        return $visibleAnswers;
    }

    /**
     * Called before a product is published. Confirms every question's
     * display_conditions still references a real, currently-earlier
     * question — and, for single_choice, a real option on it. A condition
     * can go stale if the referenced question/option was deleted, or the
     * question order changed, after the condition was originally saved.
     *
     * @param  Collection<int, \App\Models\Question>  $questions  the product's questions, eager-loaded with options
     *
     * @throws ValidationException  with a plain-English message naming the broken question
     */
    public static function assertPublishable(Collection $questions): void
    {
        $byId = $questions->keyBy('id');

        foreach ($questions as $question) {
            $conditions = $question->display_conditions['conditions'] ?? [];

            foreach ($conditions as $condition) {
                $questionId = $condition['question_id'] ?? null;
                $referenced = $questionId !== null ? $byId->get($questionId) : null;

                if (! $referenced) {
                    throw ValidationException::withMessages([
                        'display_conditions' => "\"{$question->question_text}\" has a condition based on a question that no longer exists. Edit or remove that condition before publishing.",
                    ]);
                }

                if ($referenced->sort_order >= $question->sort_order) {
                    throw ValidationException::withMessages([
                        'display_conditions' => "\"{$question->question_text}\" has a condition based on \"{$referenced->question_text}\", but that question no longer comes before it. Edit the condition, or fix the question order, before publishing.",
                    ]);
                }

                if ($referenced->type === 'single_choice') {
                    $validOptionIds = $referenced->options->pluck('id')->map(fn ($id) => (string) $id)->all();
                    $values = is_array($condition['value'] ?? null) ? $condition['value'] : [$condition['value'] ?? null];

                    foreach ($values as $value) {
                        if (! in_array((string) $value, $validOptionIds, true)) {
                            throw ValidationException::withMessages([
                                'display_conditions' => "\"{$question->question_text}\" has a condition based on an answer of \"{$referenced->question_text}\" that no longer exists. Edit that condition before publishing.",
                            ]);
                        }
                    }
                }
            }
        }
    }
}
