@props(['question' => null, 'otherQuestions' => collect()])

@php
    $initialDisplayConditions = old('display_conditions')
        ? json_decode(old('display_conditions'), true)
        : $question?->display_conditions;
    $initialLogic = $initialDisplayConditions['logic'] ?? 'and';
    $initialConditions = $initialDisplayConditions['conditions'] ?? [];
@endphp

<div>
    <x-input-label for="question_text" value="Question Text" />
    <x-text-input id="question_text" name="question_text" type="text" class="block mt-1 w-full" required autofocus
        :value="old('question_text', $question?->question_text)" />
    <x-input-error :messages="$errors->get('question_text')" class="mt-2" />
</div>

<div class="mt-4" x-data="{ count: {{ Js::from(strlen(old('description', $question?->description ?? ''))) }} }">
    <x-input-label for="description" value="Help Text (optional)" />
    <textarea id="description" name="description" rows="2" maxlength="160" @input="count = $event.target.value.length"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('description', $question?->description) }}</textarea>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Shown as an "i" tooltip next to this question in the quote builder. <span x-text="count"></span>/160</p>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="type" value="Answer Type" />
    @php $selectedType = old('type', $question?->type ?? 'single_choice'); @endphp
    <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
        <option value="single_choice" @selected($selectedType === 'single_choice')>Single choice (customer picks one option)</option>
        <option value="number" @selected($selectedType === 'number')>Number (customer types a number)</option>
        <option value="text" @selected($selectedType === 'text')>Text (customer types free text)</option>
    </select>
    <x-input-error :messages="$errors->get('type')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="sort_order" value="Sort Order" />
    <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="block mt-1 w-full"
        :value="old('sort_order', $question?->sort_order ?? 0)" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lower numbers are shown first. A condition can only depend on a question with a lower sort order than this one.</p>
    <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
</div>

<div
    class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"
    x-data="{
        otherQuestions: {{ Js::from($otherQuestions->map(fn ($q) => [
            'id' => $q->id,
            'question_text' => $q->question_text,
            'type' => $q->type,
            'options' => $q->options->map(fn ($o) => ['id' => (string) $o->id, 'label' => $o->label])->values(),
        ])) }},
        logic: '{{ $initialLogic }}',
        conditions: {{ Js::from(collect($initialConditions)->map(fn ($c) => [
            'question_id' => (string) ($c['question_id'] ?? ''),
            'operator' => $c['operator'] ?? 'equals',
            'value' => is_array($c['value'] ?? null) ? $c['value'] : (string) ($c['value'] ?? ''),
        ])->values()) }},
        enabled: {{ count($initialConditions) > 0 ? 'true' : 'false' }},

        operatorLabels: { equals: 'is', not_equals: 'is not', in: 'is one of' },

        questionFor(id) {
            return this.otherQuestions.find(q => String(q.id) === String(id)) || null;
        },
        operatorsFor(id) {
            const q = this.questionFor(id);
            if (q && q.type === 'single_choice') return ['equals', 'not_equals', 'in'];
            return ['equals', 'not_equals'];
        },

        addCondition() {
            this.conditions.push({ question_id: '', operator: 'equals', value: '' });
        },
        removeCondition(index) {
            this.conditions.splice(index, 1);
        },
        onQuestionChange(row) {
            const allowed = this.operatorsFor(row.question_id);
            if (! allowed.includes(row.operator)) row.operator = allowed[0];
            row.value = row.operator === 'in' ? [] : '';
        },
        onOperatorChange(row) {
            row.value = row.operator === 'in' ? [] : '';
        },
        toggleInValue(row, optionId) {
            if (! Array.isArray(row.value)) row.value = [];
            const i = row.value.indexOf(optionId);
            if (i === -1) row.value.push(optionId); else row.value.splice(i, 1);
        },

        submitConditions() {
            const conditions = this.conditions
                .filter(c => c.question_id !== '' && (Array.isArray(c.value) ? c.value.length > 0 : c.value !== ''))
                .map(c => ({ question_id: parseInt(c.question_id), operator: c.operator, value: c.value }));
            this.$refs.displayConditionsInput.value = (this.enabled && conditions.length) ? JSON.stringify({ logic: this.logic, conditions }) : '';
        },
    }"
    x-effect="submitConditions()"
>
    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Show this question…</h3>

    @if ($otherQuestions->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">Always shown — there are no earlier questions to base a condition on yet.</p>
    @else
        <label class="inline-flex items-center gap-2 mt-2">
            <input type="checkbox" x-model="enabled" class="rounded border-gray-300 brand-checkbox shadow-sm focus:ring-indigo-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">Only if certain earlier answers match</span>
        </label>

        <div x-show="enabled" x-cloak class="mt-4 space-y-4">
            <template x-for="(row, index) in conditions" :key="index">
                <div class="flex items-start gap-2 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <select x-model="row.question_id" @change="onQuestionChange(row)"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                            <option value="">Choose a question&hellip;</option>
                            <template x-for="q in otherQuestions" :key="q.id">
                                <option :value="String(q.id)" x-text="q.question_text" :selected="String(q.id) === String(row.question_id)"></option>
                            </template>
                        </select>

                        <select x-model="row.operator" @change="onOperatorChange(row)" x-show="row.question_id"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                            <template x-for="op in operatorsFor(row.question_id)" :key="op">
                                <option :value="op" x-text="operatorLabels[op]" :selected="op === row.operator"></option>
                            </template>
                        </select>

                        <!-- Value: single-choice equals/not_equals -->
                        <select
                            x-show="row.question_id && questionFor(row.question_id).type === 'single_choice' && row.operator !== 'in'"
                            x-model="row.value"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                            <option value="">Choose an answer&hellip;</option>
                            <template x-for="option in (questionFor(row.question_id) ? questionFor(row.question_id).options : [])" :key="option.id">
                                <option :value="option.id" x-text="option.label" :selected="option.id === row.value"></option>
                            </template>
                        </select>

                        <!-- Value: number/text equals/not_equals -->
                        <input type="text"
                            x-show="row.question_id && questionFor(row.question_id).type !== 'single_choice'"
                            x-model="row.value"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                            placeholder="Value">
                    </div>

                    <button type="button" @click="removeCondition(index)" class="text-gray-400 hover:text-red-600 mt-1.5" title="Remove condition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                </div>
            </template>

            <template x-for="(row, index) in conditions" :key="'in-' + index">
                <div x-show="row.question_id && questionFor(row.question_id).type === 'single_choice' && row.operator === 'in'" x-cloak
                    class="-mt-2 ml-3 flex flex-wrap gap-3">
                    <template x-for="option in (questionFor(row.question_id) ? questionFor(row.question_id).options : [])" :key="option.id">
                        <label class="inline-flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" :checked="Array.isArray(row.value) && row.value.includes(option.id)" @change="toggleInValue(row, option.id)"
                                class="rounded border-gray-300 brand-checkbox shadow-sm focus:ring-indigo-500">
                            <span x-text="option.label"></span>
                        </label>
                    </template>
                </div>
            </template>

            <button type="button" @click="addCondition()" class="text-sm font-medium brand-text">
                + Add another condition
            </button>

            <div x-show="conditions.length > 1" x-cloak class="pt-2 border-t border-gray-100">
                <x-input-label value="Match" />
                <select x-model="logic" class="mt-1 block w-full sm:w-48 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="and">All conditions (AND)</option>
                    <option value="or">Any condition (OR)</option>
                </select>
            </div>
        </div>
    @endif

    <input type="hidden" name="display_conditions" x-ref="displayConditionsInput">
    <x-input-error :messages="$errors->get('display_conditions')" class="mt-2" />
</div>
