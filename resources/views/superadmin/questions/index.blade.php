<x-superadmin-layout :title="$product->name.' — Questions'">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('superadmin.products.index', $business) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $business->name }}</a> /
                    {{ $product->name }}
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Questions</h2>
            </div>
            <x-primary-button onclick="window.location='{{ route('superadmin.products.questions.create', [$business, $product]) }}'">New Question</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

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
                                <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">Unpublished</span>
                            @endunless
                            @if (! empty($question->display_conditions['conditions']))
                                <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300">Conditional</span>
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
                            <a href="{{ route('superadmin.questions.options.index', $question) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Options</a>
                        @endif
                        <a href="{{ route('superadmin.questions.edit', $question) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
