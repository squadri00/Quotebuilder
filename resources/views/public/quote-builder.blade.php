<!DOCTYPE html>
<html lang="en" class="{{ request('theme') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $ogTitle = $snapshot['product']['name'].' — '.$business->name;
            $ogDescription = $snapshot['product']['description'] ?? "Get an instant quote for {$snapshot['product']['name']} from {$business->name}.";
            $ogImage = $snapshot['images'][0]['url'] ?? ($business->logo_path ? Storage::url($business->logo_path) : null);
        @endphp

        <title>{{ $ogTitle }}</title>
        <meta name="description" content="{{ $ogDescription }}">

        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
        @endif

        <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        @if ($ogImage)
            <meta name="twitter:image" content="{{ $ogImage }}">
        @endif

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Business branding — the .brand-* classes live in app.css; this
             just supplies the value. Falls back to QuoteBuilder's own
             indigo when a business hasn't set a brand_color. --}}
        <style>:root { --brand-color: {{ $business->brand_color ?? '#4f46e5' }}; }</style>

        @if (($platformSettings ?? null)?->turnstile_site_key)
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        @php
            // Flags the first $0 option on a question as the "standard"
            // pick — but only when the question actually has priced
            // alternatives, so a question where every option happens to be
            // free (no real choice being made) never gets a misleading
            // "Most Popular" tag. Only a boolean reaches the client, never
            // the real price_modifier — the public wizard deliberately
            // never exposes per-option pricing, only the final total, so
            // this can't be used to reverse-engineer the price list.
            $questionsPayload = collect($snapshot['questions'])->map(function ($q) {
                $options = collect($q['options']);
                $hasPricedAlternative = $options->contains(fn ($o) => (float) ($o['price_modifier'] ?? 0) !== 0.0);
                $standardOptionId = $hasPricedAlternative
                    ? optional($options->first(fn ($o) => (float) ($o['price_modifier'] ?? 0) === 0.0))['id']
                    : null;

                return [
                    'id' => $q['id'],
                    'question_text' => $q['question_text'],
                    'type' => $q['type'],
                    'display_conditions' => $q['display_conditions'] ?? null,
                    'options' => $options->map(fn ($o) => [
                        'id' => $o['id'],
                        'label' => $o['label'],
                        'isStandard' => $standardOptionId !== null && $o['id'] === $standardOptionId,
                    ])->values(),
                ];
            });
        @endphp
        <div
            x-data="quoteWizard(
                {{ Js::from($questionsPayload) }},
                null,
                false,
                {},
                {{ Js::from('quotebuilder:draft:'.$business->id.':'.$product->id) }}
            )"
            class="py-8 px-4 sm:py-12"
        >
            <div
                x-show="draftToastVisible"
                x-transition
                x-cloak
                class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 z-50 sm:max-w-sm sm:w-auto bg-gray-900 dark:bg-gray-700 text-white text-sm rounded-lg shadow-lg px-4 py-3 flex items-center gap-3"
                role="status"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 shrink-0 text-green-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Your progress is saved — come back anytime and pick up where you left off.</span>
                <button type="button" @click="dismissDraftToast()" class="ml-auto shrink-0 text-gray-400 hover:text-white brand-focus-ring rounded" aria-label="Dismiss">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

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
                                        <button type="button" @click="active = index" class="rounded-md overflow-hidden border-2 brand-focus-ring"
                                            :class="active === index ? 'brand-border' : 'border-transparent'"
                                            :aria-label="'View image ' + (index + 1)"
                                            :aria-current="active === index">
                                            <img :src="img" alt="" class="w-full aspect-square object-cover">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        @endif

                        {{-- Hidden on mobile — the DONE step has its own recap covering
                             the same info right where it's needed, and this sidebar sits
                             below the main card on small screens (reordered, not hidden),
                             so showing both would just repeat the same answers twice. --}}
                        <div class="hidden md:block" x-show="answeredQuestions().length > 0" x-cloak>
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Your Selections</p>
                                <ul class="space-y-1">
                                    <template x-for="item in answeredQuestions()" :key="item.question.id">
                                        <li>
                                            <button
                                                type="button"
                                                @click="goToQuestion(item.question.id)"
                                                class="w-full text-left rounded-lg px-2 py-1.5 -mx-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition brand-focus-ring"
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
                            <div
                                class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700"
                                role="progressbar"
                                aria-label="Quote progress"
                                :aria-valuenow="progressPercent"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                <div class="h-1.5 rounded-full brand-bg transition-all duration-300" :style="'width: ' + progressPercent + '%'"></div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8 dark:bg-gray-800 dark:border-gray-700">
                            <template x-for="question in questions" :key="question.id">
                                <div x-show="currentQuestionId === question.id" x-cloak>
                                    <h2
                                        class="text-xl font-bold text-gray-900 dark:text-gray-100 text-center brand-focus-ring rounded"
                                        :id="'question-heading-' + question.id"
                                        :data-question-heading="question.id"
                                        tabindex="-1"
                                        x-text="question.question_text"
                                    ></h2>

                                    <div
                                        class="mt-6 space-y-3"
                                        x-show="question.type === 'single_choice'"
                                        role="radiogroup"
                                        :aria-labelledby="'question-heading-' + question.id"
                                    >
                                        <template x-for="option in question.options" :key="option.id">
                                            <button
                                                type="button"
                                                role="radio"
                                                :aria-checked="String(answers[question.id]) === String(option.id)"
                                                @click="selectOption(question.id, option.id)"
                                                class="w-full flex items-center justify-between gap-3 text-left px-4 py-4 rounded-xl border-2 font-medium transition brand-focus-ring"
                                                :class="String(answers[question.id]) === String(option.id)
                                                    ? 'brand-selected'
                                                    : 'border-gray-200 hover:border-gray-300 text-gray-900 dark:border-gray-600 dark:hover:border-gray-500 dark:text-gray-100'"
                                            >
                                                <span x-text="option.label"></span>
                                                <span
                                                    x-show="option.isStandard"
                                                    x-cloak
                                                    class="shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                                                >Most Popular</span>
                                            </button>
                                        </template>
                                        <div class="text-center" x-show="question.options.length === 0">
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No options available for this question yet.</p>
                                            <button type="button" @click="goNext()"
                                                class="inline-flex items-center justify-center px-6 py-2.5 brand-bg rounded-lg text-sm font-semibold text-white transition brand-focus-ring">
                                                Next
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-6" x-show="question.type === 'number'">
                                        <input
                                            type="number"
                                            class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-lg text-center py-3 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                            :aria-labelledby="'question-heading-' + question.id"
                                            :value="answers[question.id]"
                                            @input="answers[question.id] = $event.target.value"
                                        >
                                        <div class="mt-4 flex justify-center">
                                            <button type="button" @click="goNext()"
                                                class="inline-flex items-center justify-center px-6 py-2.5 brand-bg rounded-lg text-sm font-semibold text-white transition brand-focus-ring">
                                                Next
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-6" x-show="question.type === 'text'">
                                        <input
                                            type="text"
                                            class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-lg text-center py-3 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                            :aria-labelledby="'question-heading-' + question.id"
                                            :value="answers[question.id]"
                                            @input="answers[question.id] = $event.target.value"
                                        >
                                        <div class="mt-4 flex justify-center">
                                            <button type="button" @click="goNext()"
                                                class="inline-flex items-center justify-center px-6 py-2.5 brand-bg rounded-lg text-sm font-semibold text-white transition brand-focus-ring">
                                                Next
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-6 text-center" x-show="currentIndex > 0">
                                        <button type="button" @click="back()" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 brand-focus-ring rounded">
                                            &larr; Back
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Customer info step -->
                            <div x-show="currentQuestionId === 'DONE'" x-cloak>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 text-center brand-focus-ring rounded" data-question-heading="DONE" tabindex="-1">Almost done!</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-1">Enter your details to see your price.</p>

                                {{-- Mobile-only recap: on small screens the "Your Selections"
                                     sidebar sits below this card in document order, so without
                                     this a customer never actually sees what they picked before
                                     being asked for their contact info. Hidden on md+ where the
                                     sidebar is already visible alongside the wizard. --}}
                                <div class="mt-4 md:hidden" x-show="answeredQuestions().length > 0" x-cloak>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                                        <template x-for="item in answeredQuestions()" :key="item.question.id">
                                            <button
                                                type="button"
                                                @click="goToQuestion(item.question.id)"
                                                class="w-full flex items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 transition brand-focus-ring"
                                            >
                                                <span class="text-gray-500 dark:text-gray-400" x-text="item.question.question_text"></span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100 text-right" x-text="answerLabel(item.question, item.value)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('quote.store', [$business, $product]) }}{{ request('theme') === 'dark' ? '?theme=dark' : '' }}" class="mt-6 space-y-4" @submit="submitForm()">
                                    @csrf

                                    @if ($errors->has('turnstile'))
                                        <p class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $errors->first('turnstile') }}</p>
                                    @endif

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

                                    {{-- Honeypot — invisible to a real visitor (off-screen, no label,
                                         never tab-reachable), but a bot filling every field blindly
                                         usually fills this too. See PublicQuoteController::store(). --}}
                                    <div style="position:absolute; left:-9999px;" aria-hidden="true">
                                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                                    </div>

                                    @if (($platformSettings ?? null)?->turnstile_site_key)
                                        <div class="cf-turnstile" data-sitekey="{{ $platformSettings->turnstile_site_key }}"></div>
                                    @endif

                                    <input type="hidden" name="answers" x-ref="answersInput">

                                    <div class="flex items-center justify-between pt-2">
                                        <button type="button" @click="back()" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 brand-focus-ring rounded">
                                            &larr; Back
                                        </button>
                                        <button type="submit"
                                            class="inline-flex items-center justify-center px-6 py-2.5 brand-bg rounded-lg text-sm font-semibold text-white transition brand-focus-ring">
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
