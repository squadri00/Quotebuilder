<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('products.index') }}" class="hover:text-gray-700">Products</a> / {{ $product->name }}
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Questions</h2>
            </div>
            <x-primary-button onclick="window.location='{{ route('products.questions.create', $product) }}'">New Question</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    @if ($questions->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No questions yet for {{ $product->name }}. Create the first one.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($questions as $question)
                <x-card class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $question->question_text }}</p>
                            @unless ($question->is_published)
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Unpublished</span>
                            @endunless
                            @if (! empty($question->display_conditions['conditions']))
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">Conditional</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ ucfirst(str_replace('_', ' ', $question->type)) }}
                            &middot; sort order {{ $question->sort_order }}
                            @if ($question->type === 'single_choice')
                                &middot; {{ $question->options()->count() }} option(s)
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        @if ($question->type === 'single_choice')
                            <a href="{{ route('questions.options.index', $question) }}" class="text-sm font-medium brand-text">Options</a>
                        @endif
                        <a href="{{ route('questions.edit', $question) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900">Edit</a>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
