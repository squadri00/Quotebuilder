<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">
            {{ __('Design Preview') }}
        </h2>
    </x-slot>

    <div class="space-y-8 max-w-5xl">
        <x-card>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                This page is a temporary style reference for Prompt 4A — not a real feature. It'll be removed once
                the actual admin screens (products, questions, rules) are built in Prompt 4.
            </p>
        </x-card>

        <!-- Colors -->
        <div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-4">Colors</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <div class="h-16 rounded-xl bg-indigo-600 border border-gray-200 dark:border-gray-700"></div>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Primary</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">indigo-600</p>
                </div>
                <div>
                    <div class="h-16 rounded-xl bg-gray-50 border border-gray-200 dark:border-gray-700"></div>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Page background</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">gray-50</p>
                </div>
                <div>
                    <div class="h-16 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"></div>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Card background</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">white</p>
                </div>
                <div>
                    <div class="h-16 rounded-xl bg-gray-900 border border-gray-200 dark:border-gray-700"></div>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Headings</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">gray-900</p>
                </div>
            </div>
        </div>

        <!-- Typography -->
        <div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-4">Typography (Inter)</h3>
            <x-card class="space-y-2">
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">Heading — bold, larger</p>
                <p class="text-base text-gray-900 dark:text-gray-100">Body text — near-black, comfortable line height for reading.</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Secondary text — gray-500, for hints and metadata.</p>
            </x-card>
        </div>

        <!-- Buttons -->
        <div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-4">Buttons</h3>
            <x-card class="flex flex-wrap items-center gap-3">
                <x-primary-button>Primary Action</x-primary-button>
                <x-secondary-button>Secondary Action</x-secondary-button>
                <x-danger-button>Destructive Action</x-danger-button>
            </x-card>
        </div>

        <!-- Card-based list (e.g. future Products list) -->
        <div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-4">Card list (e.g. Products)</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach (['Flyers' => '$49.99', 'Business Cards' => '$19.99', 'Banners' => '$89.99'] as $name => $price)
                    <x-card class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Base price {{ $price }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">Active</span>
                    </x-card>
                @endforeach
            </div>
        </div>

        <!-- Public quote builder mockup -->
        <div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-4">Public quote builder (customer view — mockup only)</h3>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 p-8 flex justify-center">
                <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <!-- Progress bar -->
                    <div class="mb-6">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                            <span>Question 1 of 3</span>
                            <span>33%</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-gray-200">
                            <div class="h-1.5 w-1/3 rounded-full bg-indigo-600"></div>
                        </div>
                    </div>

                    <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100 text-center">Paper Type</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-1">Choose the finish for your flyers.</p>

                    <div class="mt-6 space-y-3">
                        <button type="button" class="w-full text-left px-4 py-4 rounded-xl border-2 border-indigo-600 bg-indigo-50 font-medium text-indigo-700 transition">
                            Matte
                            <span class="block text-xs font-normal text-indigo-600 mt-0.5">Included</span>
                        </button>
                        <button type="button" class="w-full text-left px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-gray-300 font-medium text-gray-900 dark:text-gray-100 transition">
                            Glossy
                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400 mt-0.5">+$5.00</span>
                        </button>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button type="button" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Back</button>
                        <x-primary-button>Next</x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
