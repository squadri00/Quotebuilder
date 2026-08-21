<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">
            {{ ($editingQuoteId ?? null) ? 'Edit Quote — '.$product->name : 'New Quote — '.$product->name }}
        </h2>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <div
        x-data="{
            ...quoteWizard(
                {{ Js::from(collect($snapshot['questions'])->map(fn ($q) => [
                    'id' => $q['id'],
                    'question_text' => $q['question_text'],
                    'description' => $q['description'] ?? null,
                    'type' => $q['type'],
                    'display_conditions' => $q['display_conditions'] ?? null,
                    'options' => collect($q['options'])->map(fn ($o) => ['id' => $o['id'], 'label' => $o['label'], 'description' => $o['description'] ?? null])->values(),
                ])) }},
                {{ Js::from(route('quotes.create.price', $product)) }},
                true,
                {{ Js::from($initialAnswers ?? []) }}
            ),
        }"
        class="max-w-3xl"
    >
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Product images + Selections summary -->
            <div class="md:w-56 shrink-0 md:order-1 order-2 space-y-4">
                @if (! empty($snapshot['images']))
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-3"
                        x-data="{ active: 0, images: {{ Js::from(collect($snapshot['images'])->pluck('url')) }} }">
                        <img :src="images[active]" alt="{{ $product->name }}" class="w-full aspect-square object-cover rounded-lg">
                        <div class="mt-2 grid grid-cols-4 gap-1.5" x-show="images.length > 1">
                            <template x-for="(img, index) in images" :key="index">
                                <button type="button" @click="active = index" class="rounded-md overflow-hidden border-2"
                                    :class="active === index ? 'brand-border' : 'border-transparent'">
                                    <img :src="img" alt="" class="w-full aspect-square object-cover">
                                </button>
                            </template>
                        </div>
                    </div>
                @endif

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Running Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 transition-opacity" :class="priceLoading ? 'opacity-50' : ''">
                        <span x-show="runningTotal !== null" x-text="'$' + Number(runningTotal).toFixed(2)"></span>
                        <span x-show="runningTotal === null" class="text-gray-300 dark:text-gray-600">&mdash;</span>
                    </p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Updates as you go — go back anytime to compare options.</p>
                </div>

                <div x-show="answeredQuestions().length > 0" x-cloak>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Selections</p>
                        <ul class="space-y-1">
                            <template x-for="item in answeredQuestions()" :key="item.question.id">
                                <li>
                                    <button
                                        type="button"
                                        @click="goToQuestion(item.question.id)"
                                        class="w-full text-left rounded-lg px-2 py-1.5 -mx-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                    >
                                        <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="item.question.question_text"></span>
                                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100" x-text="answerLabel(item.question, item.value)"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main card -->
            <div class="flex-1 order-1 md:order-2">
                <div class="mb-6">
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                        <span x-text="'Step ' + stepNumber + ' of ' + totalSteps"></span>
                        <span x-text="progressPercent + '%'"></span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-1.5 rounded-full brand-bg transition-all duration-300" :style="'width: ' + progressPercent + '%'"></div>
                    </div>
                </div>

                <x-card>
                    <template x-for="question in questions" :key="question.id">
                        <div x-show="currentQuestionId === question.id" x-cloak>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 text-center flex items-center justify-center gap-1.5">
                                <span x-text="question.question_text"></span>
                                <x-wizard-tooltip bind="question.description" />
                            </h2>

                            <div class="mt-6 space-y-3" x-show="question.type === 'single_choice'">
                                <template x-for="option in question.options" :key="option.id">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="selectOption(question.id, option.id)"
                                            class="flex-1 text-left px-4 py-4 rounded-xl border-2 font-medium transition"
                                            :class="String(answers[question.id]) === String(option.id)
                                                ? 'brand-selected'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 text-gray-900 dark:text-gray-100'"
                                        >
                                            <span x-text="option.label"></span>
                                        </button>
                                        <x-wizard-tooltip bind="option.description" />
                                    </div>
                                </template>
                                <div class="text-center" x-show="question.options.length === 0">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No options available for this question yet.</p>
                                    <button type="button" @click="goNext()"
                                        class="inline-flex items-center justify-center px-6 py-2.5 brand-bg rounded-lg text-sm font-semibold text-white transition">
                                        Next
                                    </button>
                                </div>
                            </div>

                            <div class="mt-6" x-show="question.type === 'number'">
                                <input
                                    type="number"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-lg text-center py-3"
                                    :value="answers[question.id]"
                                    @input="answers[question.id] = $event.target.value"
                                >
                                <div class="mt-4 flex justify-center">
                                    <button type="button" @click="goNext()"
                                        class="inline-flex items-center justify-center px-6 py-2.5 brand-bg rounded-lg text-sm font-semibold text-white transition">
                                        Next
                                    </button>
                                </div>
                            </div>

                            <div class="mt-6" x-show="question.type === 'text'">
                                <input
                                    type="text"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-lg text-center py-3"
                                    :value="answers[question.id]"
                                    @input="answers[question.id] = $event.target.value"
                                >
                                <div class="mt-4 flex justify-center">
                                    <button type="button" @click="goNext()"
                                        class="inline-flex items-center justify-center px-6 py-2.5 brand-bg rounded-lg text-sm font-semibold text-white transition">
                                        Next
                                    </button>
                                </div>
                            </div>

                            <div class="mt-6 text-center" x-show="currentIndex > 0">
                                <button type="button" @click="back()" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                    &larr; Back
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Final step: every question's already been answered and the
                         running total has been visible the whole way through, so
                         this submits straight into Review — nothing left to confirm. -->
                    <div x-show="currentQuestionId === 'DONE'" x-cloak class="text-center py-6">
                        <svg class="animate-spin h-6 w-6 mx-auto brand-text" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Taking you to Review&hellip;</p>

                        <form method="POST" action="{{ route('quotes.create.review', $product) }}" class="mt-4" x-ref="doneForm">
                            @csrf
                            <input type="hidden" name="answers" x-ref="answersInput">
                            @if ($editingQuoteId ?? null)
                                <input type="hidden" name="editing_quote_id" value="{{ $editingQuoteId }}">
                            @endif
                            <button type="button" @click="back()" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                &larr; Back
                            </button>
                        </form>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
