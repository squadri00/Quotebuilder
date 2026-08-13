<?php

namespace App\Services;

use App\Models\Option;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteAnswer;
use App\Models\Question;
use App\Support\QuestionVisibility;

/**
 * The answer-sanitizing/recording pipeline shared by the public quote
 * builder (App\Http\Controllers\PublicQuoteController) and the internal,
 * staff-facing one (App\Http\Controllers\InternalQuoteController) — both
 * read the exact same product published_snapshot and run the exact same
 * RulesEngine calculation; this is what keeps the two entry points from
 * silently drifting apart over time.
 */
class QuoteAnswerProcessor
{
    /**
     * Casts raw submitted answers to the right type and drops anything
     * that doesn't belong: an answer to a question outside this snapshot,
     * an empty value, or — via QuestionVisibility::filterAnswers() — a
     * stale/tampered answer to a question that shouldn't have been
     * reachable given the *other* answers actually given. Never trust the
     * client's own idea of what was visible.
     *
     * @param  array<int, mixed>  $rawAnswers  question_id (possibly a string key) => raw value
     * @return array<int, mixed>
     */
    public function sanitizeAnswers(array $snapshot, array $rawAnswers): array
    {
        $snapshotQuestionIds = collect($snapshot['questions'] ?? [])->pluck('id')->all();

        $answers = [];

        foreach ($rawAnswers as $questionId => $value) {
            $questionId = (int) $questionId;

            if (! in_array($questionId, $snapshotQuestionIds, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $answers[$questionId] = is_numeric($value) ? $value + 0 : $value;
        }

        return QuestionVisibility::filterAnswers($snapshot['questions'] ?? [], $answers);
    }

    /**
     * @return array{base_price: float, final_price: float, applied_rules: array}
     */
    public function calculate(array $snapshot, array $answers): array
    {
        return (new RulesEngine)->calculateFromSnapshot($snapshot, $answers);
    }

    /**
     * A plain-English question → answer list, in question order — meant
     * to be snapshotted into Quote.meta['selections'] at creation time
     * (same "freeze it so it never drifts" reasoning as base_price/
     * applied_rules/tax_lines already stored alongside it) so a printed
     * quotation or PDF always shows exactly what was quoted, even if the
     * product's questions/options are renamed or deleted afterward.
     *
     * @return array<int, array{question: string, answer: mixed}>
     */
    public function buildSelections(array $snapshot, array $answers): array
    {
        $selections = [];

        foreach ($snapshot['questions'] ?? [] as $question) {
            if (! array_key_exists($question['id'], $answers)) {
                continue;
            }

            $value = $answers[$question['id']];

            if ($question['type'] === 'single_choice') {
                $option = collect($question['options'])->firstWhere('id', $value);
                $value = $option['label'] ?? $value;
            }

            $selections[] = [
                'question' => $question['question_text'],
                'answer' => $value,
            ];
        }

        return $selections;
    }

    /**
     * Persists one QuoteAnswer row per answer, against the product's
     * *live* question/option rows — almost always still there, but if a
     * business deleted one after publishing (while this snapshot was
     * still live), that single answer is skipped rather than failing the
     * whole quote; the price was already calculated correctly from the
     * snapshot regardless of whether this bookkeeping succeeds.
     */
    public function recordAnswers(Product $product, Quote $quote, array $snapshot, array $answers): void
    {
        $liveQuestionIds = Question::where('product_id', $product->id)->pluck('id')->all();
        $liveOptionIds = Option::whereIn('question_id', $liveQuestionIds)->pluck('id')->all();

        foreach ($snapshot['questions'] ?? [] as $question) {
            $questionId = $question['id'];

            if (! array_key_exists($questionId, $answers) || ! in_array($questionId, $liveQuestionIds, true)) {
                continue;
            }

            $value = $answers[$questionId];
            $isChoice = $question['type'] === 'single_choice';

            if ($isChoice && ! in_array($value, $liveOptionIds, true)) {
                continue;
            }

            QuoteAnswer::create([
                'business_id' => $product->business_id,
                'quote_id' => $quote->id,
                'question_id' => $questionId,
                'option_id' => $isChoice ? $value : null,
                'answer_value' => $isChoice ? null : (string) $value,
            ]);
        }
    }
}
