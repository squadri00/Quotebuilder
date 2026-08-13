@props(['products', 'initial' => [], 'rule' => null])

@php
$initial = array_merge([
    'product_id' => '',
    'question_id' => '',
    'operator' => 'equals',
    'condition_option_id' => '',
    'condition_number' => '',
    'condition_text' => '',
    'action_type' => 'add_fixed',
    'action_amount' => '',
    'action_percent' => '',
    'action_option_id' => '',
    'action_price_modifier' => '',
], array_map(fn ($v) => $v ?? '', $initial));
@endphp

<div
    x-data="{
        products: {{ Js::from($products) }},
        productId: '{{ old('product_id', $initial['product_id']) }}',
        questionId: '{{ old('question_id', $initial['question_id']) }}',
        operator: '{{ old('operator', $initial['operator']) }}',
        actionType: '{{ old('action_type', $initial['action_type']) }}',

        operatorLabels: {
            equals: 'is equal to',
            not_equals: 'is not equal to',
            greater_than: 'is greater than',
            less_than: 'is less than',
            greater_than_or_equal: 'is at least',
            less_than_or_equal: 'is at most',
        },

        get selectedProduct() {
            return this.products.find(p => String(p.id) === String(this.productId)) || null;
        },
        get questions() {
            return this.selectedProduct ? this.selectedProduct.questions : [];
        },
        get selectedQuestion() {
            return this.questions.find(q => String(q.id) === String(this.questionId)) || null;
        },
        get questionType() {
            return this.selectedQuestion ? this.selectedQuestion.type : null;
        },
        get allowedOperators() {
            if (this.questionType === 'number') {
                return ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal'];
            }
            return ['equals', 'not_equals'];
        },
        get allOptionsInProduct() {
            return this.questions.flatMap(q => q.options.map(o => ({ id: o.id, label: q.question_text + ' — ' + o.label })));
        },

        onProductChange() {
            this.questionId = '';
        },
        onQuestionChange() {
            if (! this.allowedOperators.includes(this.operator)) {
                this.operator = this.allowedOperators[0];
            }
        },
    }"
>
    <div>
        <x-input-label for="name" value="Rule Name" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required autofocus
            :value="old('name', $rule?->name)" placeholder="e.g. Glossy surcharge" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">When...</h3>

        <div>
            <x-input-label for="product_id" value="Product" />
            <select id="product_id" name="product_id" @change="productId = $event.target.value; onProductChange()"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="">Choose a product&hellip;</option>
                <template x-for="product in products" :key="product.id">
                    <option :value="product.id" x-text="product.name" :selected="String(product.id) === productId"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
        </div>

        <div class="mt-4" x-show="productId" x-cloak>
            <x-input-label for="question_id" value="Question" />
            <select id="question_id" name="question_id" @change="questionId = $event.target.value; onQuestionChange()"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="">Choose a question&hellip;</option>
                <template x-for="question in questions" :key="question.id">
                    <option :value="question.id" x-text="question.question_text" :selected="String(question.id) === questionId"></option>
                </template>
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="productId && questions.length === 0">
                This product has no questions yet.
            </p>
            <x-input-error :messages="$errors->get('question_id')" class="mt-2" />
        </div>

        <div class="mt-4" x-show="questionId" x-cloak>
            <x-input-label for="operator" value="Comparison" />
            <select id="operator" name="operator" @change="operator = $event.target.value"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <template x-for="op in allowedOperators" :key="op">
                    <option :value="op" x-text="operatorLabels[op]" :selected="op === operator"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('operator')" class="mt-2" />
        </div>

        <!-- Condition value: depends on the selected question's type -->
        <div class="mt-4" x-show="questionType === 'single_choice'" x-cloak>
            <x-input-label for="condition_option_id" value="Answer" />
            <select id="condition_option_id" name="condition_option_id"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="">Choose an option&hellip;</option>
                <template x-for="option in (selectedQuestion ? selectedQuestion.options : [])" :key="option.id">
                    <option :value="option.id" x-text="option.label"
                        :selected="String(option.id) === '{{ old('condition_option_id', $initial['condition_option_id']) }}'"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('condition_option_id')" class="mt-2" />
        </div>

        <div class="mt-4" x-show="questionType === 'number'" x-cloak>
            <x-input-label for="condition_number" value="Number" />
            <x-text-input id="condition_number" name="condition_number" type="number" step="any" class="block mt-1 w-full"
                :value="old('condition_number', $initial['condition_number'])" />
            <x-input-error :messages="$errors->get('condition_number')" class="mt-2" />
        </div>

        <div class="mt-4" x-show="questionType === 'text'" x-cloak>
            <x-input-label for="condition_text" value="Text" />
            <x-text-input id="condition_text" name="condition_text" type="text" class="block mt-1 w-full"
                :value="old('condition_text', $initial['condition_text'])" />
            <x-input-error :messages="$errors->get('condition_text')" class="mt-2" />
        </div>
    </div>

    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">...then</h3>

        <div>
            <x-input-label for="action_type" value="Action" />
            <select id="action_type" name="action_type" x-model="actionType"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="add_fixed">Add a fixed dollar amount</option>
                <option value="add_percentage">Add a percentage</option>
                <option value="set_option_price">Set a specific option's price</option>
            </select>
            <x-input-error :messages="$errors->get('action_type')" class="mt-2" />
        </div>

        <div class="mt-4" x-show="actionType === 'add_fixed'" x-cloak>
            <x-input-label for="action_amount" value="Amount to add ($)" />
            <x-text-input id="action_amount" name="action_amount" type="number" step="0.01" class="block mt-1 w-full"
                :value="old('action_amount', $initial['action_amount'])" />
            <x-input-error :messages="$errors->get('action_amount')" class="mt-2" />
        </div>

        <div class="mt-4" x-show="actionType === 'add_percentage'" x-cloak>
            <x-input-label for="action_percent" value="Percentage to add (%)" />
            <x-text-input id="action_percent" name="action_percent" type="number" step="0.01" class="block mt-1 w-full"
                :value="old('action_percent', $initial['action_percent'])" />
            <x-input-error :messages="$errors->get('action_percent')" class="mt-2" />
        </div>

        <div x-show="actionType === 'set_option_price'" x-cloak>
            <div class="mt-4">
                <x-input-label for="action_option_id" value="Option to override" />
                <select id="action_option_id" name="action_option_id"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="">Choose an option&hellip;</option>
                    <template x-for="option in allOptionsInProduct" :key="option.id">
                        <option :value="option.id" x-text="option.label"
                            :selected="String(option.id) === '{{ old('action_option_id', $initial['action_option_id']) }}'"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="productId && allOptionsInProduct.length === 0">
                    This product has no options yet.
                </p>
                <x-input-error :messages="$errors->get('action_option_id')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="action_price_modifier" value="New price ($)" />
                <x-text-input id="action_price_modifier" name="action_price_modifier" type="number" step="0.01" class="block mt-1 w-full"
                    :value="old('action_price_modifier', $initial['action_price_modifier'])" />
                <x-input-error :messages="$errors->get('action_price_modifier')" class="mt-2" />
            </div>
        </div>
    </div>
</div>
