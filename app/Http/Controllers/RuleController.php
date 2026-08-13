<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Product;
use App\Models\Question;
use App\Models\Rule;
use App\Support\RuleDescriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RuleController extends Controller
{
    private const OPERATORS_BY_TYPE = [
        'single_choice' => ['equals', 'not_equals'],
        'number' => ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal'],
        'text' => ['equals', 'not_equals'],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $business = Auth::user()->business;
        $accessibleProductIds = $business->productsAccessibleTo(Auth::user())->pluck('id');

        $rules = Rule::with('product')->whereIn('product_id', $accessibleProductIds)->latest()->get()->map(fn (Rule $rule) => [
            'rule' => $rule,
            'sentence' => RuleDescriber::describe($rule),
        ]);

        return view('rules.index', compact('rules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('rules.create', [
            'products' => $this->productsForForm(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        [$conditionLogic, $actionLogic, $productId] = $this->validateAndBuild($request);

        Rule::create([
            'product_id' => $productId,
            'name' => $request->name,
            'condition_logic' => $conditionLogic,
            'action_logic' => $actionLogic,
        ]);

        return redirect()->route('rules.index')->with('status', 'Rule created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rule $rule): View
    {
        abort_unless(Auth::user()->canAccessProduct($rule->product), 404);

        return view('rules.edit', [
            'rule' => $rule,
            'products' => $this->productsForForm(),
            'initial' => $this->initialValuesFor($rule),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rule $rule): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($rule->product), 404);

        [$conditionLogic, $actionLogic, $productId] = $this->validateAndBuild($request);

        $rule->update([
            'product_id' => $productId,
            'name' => $request->name,
            'condition_logic' => $conditionLogic,
            'action_logic' => $actionLogic,
        ]);

        return redirect()->route('rules.index')->with('status', 'Rule updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rule $rule): RedirectResponse
    {
        abort_unless(Auth::user()->canAccessProduct($rule->product), 404);

        $rule->delete();

        return redirect()->route('rules.index')->with('status', 'Rule deleted.');
    }

    /**
     * Products with their questions (in order) and each question's
     * options, for the client-side form to filter through as the
     * business owner picks a product then a question.
     */
    private function productsForForm(): Collection
    {
        return Auth::user()->business->productsAccessibleTo(Auth::user())
            ->with(['questions' => function ($query) {
                $query->orderBy('sort_order');
            }, 'questions.options'])->orderBy('name')->get();
    }

    /**
     * Validate the submitted form and translate it into the
     * condition_logic / action_logic shape the RulesEngine expects.
     *
     * @return array{0: array, 1: array, 2: int} [condition_logic, action_logic, product_id]
     */
    private function validateAndBuild(Request $request): array
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['required'],
            'question_id' => ['required'],
            'operator' => ['required', 'in:equals,not_equals,greater_than,less_than,greater_than_or_equal,less_than_or_equal'],
            'action_type' => ['required', 'in:add_fixed,add_percentage,set_option_price'],
        ]);

        $product = Product::find($request->product_id);

        if (! $product || ! Auth::user()->canAccessProduct($product)) {
            throw ValidationException::withMessages(['product_id' => 'Please choose a valid product.']);
        }

        $question = Question::where('product_id', $product->id)->find($request->question_id);

        if (! $question) {
            throw ValidationException::withMessages(['question_id' => 'Please choose a question that belongs to the selected product.']);
        }

        $allowedOperators = self::OPERATORS_BY_TYPE[$question->type] ?? ['equals', 'not_equals'];

        if (! in_array($request->operator, $allowedOperators, true)) {
            throw ValidationException::withMessages(['operator' => "That comparison isn't valid for this question's type."]);
        }

        $conditionValue = match ($question->type) {
            'single_choice' => $this->requireOptionFor($question, $request->input('condition_option_id'), 'condition_option_id'),
            'number' => $this->requireNumeric($request->input('condition_number'), 'condition_number'),
            default => $this->requireString($request->input('condition_text'), 'condition_text'),
        };

        $conditionLogic = [
            'question_id' => $question->id,
            'operator' => $request->operator,
            'value' => $conditionValue,
        ];

        $actionLogic = match ($request->action_type) {
            'add_fixed' => [
                'type' => 'add_fixed',
                'amount' => $this->requireNumeric($request->input('action_amount'), 'action_amount'),
            ],
            'add_percentage' => [
                'type' => 'add_percentage',
                'percent' => $this->requireNumeric($request->input('action_percent'), 'action_percent'),
            ],
            'set_option_price' => [
                'type' => 'set_option_price',
                'option_id' => $this->requireOptionInProduct($product, $request->input('action_option_id'), 'action_option_id')->id,
                'price_modifier' => $this->requireNumeric($request->input('action_price_modifier'), 'action_price_modifier'),
            ],
        };

        return [$conditionLogic, $actionLogic, $product->id];
    }

    private function requireOptionFor(Question $question, mixed $optionId, string $field): int
    {
        $option = Option::where('question_id', $question->id)->find($optionId);

        if (! $option) {
            throw ValidationException::withMessages([$field => 'Please choose a valid option.']);
        }

        return $option->id;
    }

    private function requireOptionInProduct(Product $product, mixed $optionId, string $field): Option
    {
        $option = Option::whereHas('question', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })->find($optionId);

        if (! $option) {
            throw ValidationException::withMessages([$field => 'Please choose a valid option.']);
        }

        return $option;
    }

    private function requireNumeric(mixed $value, string $field): float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw ValidationException::withMessages([$field => 'Please enter a number.']);
        }

        return (float) $value;
    }

    private function requireString(mixed $value, string $field): string
    {
        if ($value === null || trim((string) $value) === '') {
            throw ValidationException::withMessages([$field => 'Please enter a value.']);
        }

        return (string) $value;
    }

    /**
     * Reconstruct form field values from a rule's stored JSON, so the
     * edit form can pre-select everything. Rules created outside this
     * form with multiple conditions only get their first condition
     * shown/editable here.
     */
    private function initialValuesFor(Rule $rule): array
    {
        $condition = $rule->condition_logic ?? [];

        if (array_is_list($condition)) {
            $condition = $condition[0] ?? [];
        }

        $question = Question::find($condition['question_id'] ?? null);
        $action = $rule->action_logic ?? [];

        $initial = [
            'product_id' => $rule->product_id,
            'question_id' => $condition['question_id'] ?? null,
            'operator' => $condition['operator'] ?? 'equals',
            'condition_option_id' => null,
            'condition_number' => null,
            'condition_text' => null,
            'action_type' => $action['type'] ?? 'add_fixed',
            'action_amount' => $action['amount'] ?? null,
            'action_percent' => $action['percent'] ?? null,
            'action_option_id' => $action['option_id'] ?? null,
            'action_price_modifier' => $action['price_modifier'] ?? null,
        ];

        if ($question?->type === 'single_choice') {
            $initial['condition_option_id'] = $condition['value'] ?? null;
        } elseif ($question?->type === 'number') {
            $initial['condition_number'] = $condition['value'] ?? null;
        } else {
            $initial['condition_text'] = $condition['value'] ?? null;
        }

        return $initial;
    }
}
