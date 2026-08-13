<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Option;
use App\Models\Product;
use App\Models\Question;
use App\Models\Rule;
use App\Support\RuleDescriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * See SuperAdmin\ProductController's docblock.
 */
class RuleController extends Controller
{
    private const OPERATORS_BY_TYPE = [
        'single_choice' => ['equals', 'not_equals'],
        'number' => ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal'],
        'text' => ['equals', 'not_equals'],
    ];

    public function index(Business $business): View
    {
        $productIds = $business->products()->pluck('id');

        $rules = Rule::with('product')->whereIn('product_id', $productIds)->latest()->get()->map(fn (Rule $rule) => [
            'rule' => $rule,
            'sentence' => RuleDescriber::describe($rule),
        ]);

        return view('superadmin.rules.index', compact('business', 'rules'));
    }

    public function create(Business $business): View
    {
        return view('superadmin.rules.create', [
            'business' => $business,
            'products' => $this->productsForForm($business),
        ]);
    }

    public function store(Request $request, Business $business): RedirectResponse
    {
        [$conditionLogic, $actionLogic, $productId] = $this->validateAndBuild($request, $business);

        $rule = Rule::create([
            'business_id' => $business->id,
            'product_id' => $productId,
            'name' => $request->name,
            'condition_logic' => $conditionLogic,
            'action_logic' => $actionLogic,
        ]);

        AuditLog::record(
            $request->user('admin'),
            'rule.created',
            $business,
            "Created rule \"{$rule->name}\" (#{$rule->id})."
        );

        return redirect()->route('superadmin.rules.index', $business)->with('status', 'Rule created.');
    }

    public function edit(Rule $rule): View
    {
        $business = $rule->product->business;

        return view('superadmin.rules.edit', [
            'business' => $business,
            'rule' => $rule,
            'products' => $this->productsForForm($business),
            'initial' => $this->initialValuesFor($rule),
        ]);
    }

    public function update(Request $request, Rule $rule): RedirectResponse
    {
        $business = $rule->product->business;

        [$conditionLogic, $actionLogic, $productId] = $this->validateAndBuild($request, $business);

        $rule->update([
            'product_id' => $productId,
            'name' => $request->name,
            'condition_logic' => $conditionLogic,
            'action_logic' => $actionLogic,
        ]);

        AuditLog::record(
            $request->user('admin'),
            'rule.updated',
            $business,
            "Updated rule \"{$rule->name}\" (#{$rule->id})."
        );

        return redirect()->route('superadmin.rules.index', $business)->with('status', 'Rule updated.');
    }

    public function destroy(Request $request, Rule $rule): RedirectResponse
    {
        $business = $rule->product->business;
        $name = $rule->name;

        $rule->delete();

        AuditLog::record(
            $request->user('admin'),
            'rule.deleted',
            $business,
            "Deleted rule \"{$name}\"."
        );

        return redirect()->route('superadmin.rules.index', $business)->with('status', 'Rule deleted.');
    }

    private function productsForForm(Business $business): Collection
    {
        return $business->products()
            ->with(['questions' => function ($query) {
                $query->orderBy('sort_order');
            }, 'questions.options'])->orderBy('name')->get();
    }

    /**
     * @return array{0: array, 1: array, 2: int}
     */
    private function validateAndBuild(Request $request, Business $business): array
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['required'],
            'question_id' => ['required'],
            'operator' => ['required', 'in:equals,not_equals,greater_than,less_than,greater_than_or_equal,less_than_or_equal'],
            'action_type' => ['required', 'in:add_fixed,add_percentage,set_option_price'],
        ]);

        $product = Product::where('business_id', $business->id)->find($request->product_id);

        if (! $product) {
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
