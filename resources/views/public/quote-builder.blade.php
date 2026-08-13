<!DOCTYPE html>
<html lang="en" class="{{ request('theme') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $snapshot['product']['name'] }} — {{ $business->name }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Business branding — the .brand-* classes live in app.css; this
             just supplies the value. Falls back to QuoteBuilder's own
             indigo when a business hasn't set a brand_color. --}}
        <style>:root { --brand-color: {{ $business->brand_color ?? '#4f46e5' }}; }</style>
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <div
            x-data="quoteWizard({{ Js::from(collect($snapshot['questions'])->map(fn ($q) => [
                'id' => $q['id'],
                'question_text' => $q['question_text'],
                'type' => $q['type'],
                'display_conditions' => $q['display_conditions'] ?? null,
                'options' => collect($q['options'])->map(fn ($o) => ['id' => $o['id'], 'label' => $o['label']])->values(),
            ])) }})"
            class="min-h-screen py-8 px-4 sm:py-12"
        >
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $business->name }}</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $snapshot['product']['name'] }}</h1>
                    @if ($snapshot['product']['description'])
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $snapshot['product']['description'] }}</p>
                    @endif
                </div>

                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Product images + Your Selections summary -->
                    <div class="md:w-56 shrink-0 md:order-1 order-2 space-y-4">
                        @if (! empty($snapshot['images']))
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-3 dark:bg-gray-800 dark:border-gray-700"
                                x-data="{ active: 0, images: {{ Js::from(collect($snapshot['images'])->pluck('url')) }} }">
                                <img :src="images[active]" alt="{{ $snapshot['product']['name'] }}" class="w-full aspect-square object-cover rounded-lg">
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

                        <div x-show="answeredQuestions().length > 0" x-cloak>
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Your Selections</p>
                                <ul class="space-y-1">
                                    <template x-for="item in answeredQuestions()" :key="item.question.id">
                                        <li>
                                            <button
                                                type="button"
                                                @click="goToQuestion(item.question.id)"
                                                class="w-full text-left rounded-lg px-2 py-1.5 -mx-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
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

                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8 dark:bg-gray-800 dark:border-gray-700">
                            <template x-for="question in questions" :key="question.id">
                                <div x-show="currentQuestionId === question.id" x-cloak>
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 text-center" x-text="question.question_text"></h2>

                                    <div class="mt-6 space-y-3" x-show="question.type === 'single_choice'">
                                        <template x-for="option in question.options" :key="option.id">
                                            <button
                                                type="button"
                                                @click="selectOption(question.id, option.id)"
                                                class="w-full text-left px-4 py-4 rounded-xl border-2 font-medium transition"
                                                :class="String(answers[question.id]) === String(option.id)
                                                    ? 'brand-selected'
                                                    : 'border-gray-200 hover:border-gray-300 text-gray-900 dark:border-gray-600 dark:hover:border-gray-500 dark:text-gray-100'"
                                            >
                                                <span x-text="option.label"></span>
                                            </button>
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
                                            class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-lg text-center py-3 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
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
                                            class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-lg text-center py-3 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
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

                            <!-- Customer info step -->
                            <div x-show="currentQuestionId === 'DONE'" x-cloak>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 text-center">Almost done!</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-1">Enter your details to see your price.</p>

                                <form method="POST" action="{{ route('quote.store', [$business, $product]) }}{{ request('theme') === 'dark' ? '?theme=dark' : '' }}" class="mt-6 space-y-4" @submit="submitForm()">
                                    @csrf

                                    <div>
                                        <label for="customer_name" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1.5">Your Name</label>
                                        <input id="customer_name" name="customer_name" type="text" required
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm py-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                    </div>

                                    <div>
                                        <label for="customer_email" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1.5">Email Address</label>
                                        <input id="customer_email" name="customer_email" type="email" required
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm py-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                    </div>

                                    <input type="hidden" name="answers" x-ref="answersInput">

                                    <div class="flex items-center justify-between pt-2">
                                        <button type="button" @click="back()" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                            &larr; Back
                                        </button>
                                        <button type="submit"
                                            class="inline-flex items-center justify-center px-6 py-2.5 brand-bg rounded-lg text-sm font-semibold text-white transition">
                                            Get My Quote
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('public._powered-by')
        @include('public._embed-resize')
    </body>
</html>
