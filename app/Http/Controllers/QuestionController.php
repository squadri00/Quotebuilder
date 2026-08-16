<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Question;
use App\Support\DeletionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionController extends Controller
{
    private const OPERATORS_BY_TYPE = [
        'single_choice' => ['equals', 'not_equals', 'in'],
        'number' => ['equals', 'not_equals'],
        'text' => ['equals', 'not_equals'],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Product $product): View
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);

        $questions = $product->questions()->orderBy('sort_order')->get();

        return view('questions.index', compact('product', 'questions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Product $product): View
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);

        return view('questions.create', [
            'product' => $product,
            'otherQuestions' => $this->otherQuestionsFor($product),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($product), 404);

        $validated = $this->validateQuestion($request, $product);

        $product->questions()->create($validated);

        return redirect()->route('products.questions.index', $product)->with('status', 'Question created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question): View
    {
        abort_unless(Auth::user()->canAccessProduct($question->product), 404);

        return view('questions.edit', [
            'question' => $question,
            'otherQuestions' => $this->otherQuestionsFor($question->product, exclude: $question->id),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($question->product), 404);

        $validated = $this->validateQuestion($request, $question->product, exclude: $question->id);

        $question->update($validated);

        return redirect()->route('products.questions.index', $question->product_id)->with('status', 'Question updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($question->product), 404);

        $blockers = DeletionGuard::blockersForQuestion($question);

        if (! empty($blockers)) {
            return redirect()->route('products.questions.index', $question->product_id)
                ->with('error', "Can't delete \"{$question->question_text}\" — it's used by ".DeletionGuard::joinList($blockers).'. Edit or remove that first, then try again.');
        }

        $productId = $question->product_id;

        $question->delete();

        return redirect()->route('products.questions.index', $productId)->with('status', 'Question deleted.');
    }

    /**
     * Every other question in the product, with its options, for the
     * condition-builder's "depends on" dropdowns. Sort_order-based
     * "must be earlier" filtering happens at validation time instead of
     * here — a business can freely reorder questions after the fact, so
     * offering every question (not just currently-earlier ones) avoids
     * the dropdown silently hiding a question the moment its sort_order
     * changes to be later, without any explanation of why.
     */
    private function otherQuestionsFor(Product $product, ?int $exclude = null): Collection
    {
        return $product->questions()
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude))
            ->orderBy('sort_order')
            ->with('options')
            ->get();
    }

    private function validateQuestion(Request $request, Product $product, ?int $exclude = null): array
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:single_choice,number,text'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['display_conditions'] = $this->validateDisplayConditions(
            $request,
            $product,
            (int) ($validated['sort_order'] ?? 0),
            $exclude
        );

        return $validated;
    }

    /**
     * Decodes and validates the hidden display_conditions JSON field the
     * Alpine condition builder serializes on submit. Returns null for
     * "always show" (empty/absent), otherwise the validated
     * {logic, conditions} shape RulesEngine-style code expects.
     */
    private function validateDisplayConditions(Request $request, Product $product, int $sortOrder, ?int $exclude): ?array
    {
        $raw = $request->input('display_conditions');

        if (! $raw) {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || empty($decoded['conditions'])) {
            return null;
        }

        $candidateQuestions = $product->questions()
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude))
            ->with('options')
            ->get()
            ->keyBy('id');

        $conditions = [];

        foreach ($decoded['conditions'] as $condition) {
            $questionId = (int) ($condition['question_id'] ?? 0);
            $referenced = $candidateQuestions->get($questionId);

            if (! $referenced) {
                throw ValidationException::withMessages([
                    'display_conditions' => 'One of the conditions references a question that no longer exists.',
                ]);
            }

            if ($referenced->sort_order >= $sortOrder) {
                throw ValidationException::withMessages([
                    'display_conditions' => "\"{$referenced->question_text}\" doesn't come before this question in the order — a condition can only depend on an earlier question.",
                ]);
            }

            $allowedOperators = self::OPERATORS_BY_TYPE[$referenced->type] ?? ['equals', 'not_equals'];
            $operator = $condition['operator'] ?? 'equals';

            if (! in_array($operator, $allowedOperators, true)) {
                throw ValidationException::withMessages([
                    'display_conditions' => "\"{$operator}\" isn't a valid comparison for \"{$referenced->question_text}\".",
                ]);
            }

            $conditions[] = [
                'question_id' => $referenced->id,
                'operator' => $operator,
                'value' => $this->validatedConditionValue($referenced, $operator, $condition['value'] ?? null),
            ];
        }

        if (empty($conditions)) {
            return null;
        }

        return [
            'logic' => ($decoded['logic'] ?? 'and') === 'or' ? 'or' : 'and',
            'conditions' => $conditions,
        ];
    }

    private function validatedConditionValue(Question $referenced, string $operator, mixed $value): mixed
    {
        if ($referenced->type === 'single_choice') {
            $optionIds = $referenced->options->pluck('id')->map(fn ($id) => (string) $id)->all();

            if ($operator === 'in') {
                $values = array_values(array_intersect(array_map('strval', (array) $value), $optionIds));

                if (empty($values)) {
                    throw ValidationException::withMessages([
                        'display_conditions' => "Choose at least one valid answer for \"{$referenced->question_text}\".",
                    ]);
                }

                return $values;
            }

            if (! in_array((string) $value, $optionIds, true)) {
                throw ValidationException::withMessages([
                    'display_conditions' => "Choose a valid answer for \"{$referenced->question_text}\".",
                ]);
            }

            return (string) $value;
        }

        if ($value === null || $value === '') {
            throw ValidationException::withMessages([
                'display_conditions' => "Enter a value to compare \"{$referenced->question_text}\" against.",
            ]);
        }

        return (string) $value;
    }
}
