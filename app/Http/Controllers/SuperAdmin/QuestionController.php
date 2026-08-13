<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Product;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * See SuperAdmin\ProductController's docblock — same idea, same
 * validation/condition-remapping rules as the business-side
 * QuestionController, just reached via an explicit {business}/{product}
 * instead of Auth::user()->business, and with no canAccessProduct() gate.
 */
class QuestionController extends Controller
{
    private const OPERATORS_BY_TYPE = [
        'single_choice' => ['equals', 'not_equals', 'in'],
        'number' => ['equals', 'not_equals'],
        'text' => ['equals', 'not_equals'],
    ];

    public function index(Business $business, Product $product): View
    {
        abort_unless($product->business_id === $business->id, 404);

        $questions = $product->questions()->orderBy('sort_order')->get();

        return view('superadmin.questions.index', compact('business', 'product', 'questions'));
    }

    public function create(Business $business, Product $product): View
    {
        abort_unless($product->business_id === $business->id, 404);

        return view('superadmin.questions.create', [
            'business' => $business,
            'product' => $product,
            'otherQuestions' => $this->otherQuestionsFor($product),
        ]);
    }

    public function store(Request $request, Business $business, Product $product): RedirectResponse
    {
        abort_unless($product->business_id === $business->id, 404);

        $validated = $this->validateQuestion($request, $product);
        $validated['business_id'] = $business->id;

        $question = $product->questions()->create($validated);

        AuditLog::record(
            $request->user('admin'),
            'question.created',
            $business,
            "Created question \"{$question->question_text}\" on \"{$product->name}\" (#{$product->id})."
        );

        return redirect()->route('superadmin.products.questions.index', [$business, $product])->with('status', 'Question created.');
    }

    public function edit(Question $question): View
    {
        $business = $question->product->business;

        return view('superadmin.questions.edit', [
            'business' => $business,
            'question' => $question,
            'otherQuestions' => $this->otherQuestionsFor($question->product, exclude: $question->id),
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $business = $question->product->business;

        $validated = $this->validateQuestion($request, $question->product, exclude: $question->id);

        $question->update($validated);

        AuditLog::record(
            $request->user('admin'),
            'question.updated',
            $business,
            "Updated question \"{$question->question_text}\" (#{$question->id})."
        );

        return redirect()->route('superadmin.products.questions.index', [$business, $question->product_id])->with('status', 'Question updated.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse
    {
        $business = $question->product->business;
        $productId = $question->product_id;
        $text = $question->question_text;

        $question->delete();

        AuditLog::record(
            $request->user('admin'),
            'question.deleted',
            $business,
            "Deleted question \"{$text}\"."
        );

        return redirect()->route('superadmin.products.questions.index', [$business, $productId])->with('status', 'Question deleted.');
    }

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
