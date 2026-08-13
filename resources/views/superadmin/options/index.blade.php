<x-superadmin-layout :title="$question->question_text.' — Options'">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('superadmin.products.index', $business) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $business->name }}</a> /
                    <a href="{{ route('superadmin.products.questions.index', [$business, $question->product_id]) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $question->product->name }}</a> /
                    {{ $question->question_text }}
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Options</h2>
            </div>
            <x-primary-button onclick="window.location='{{ route('superadmin.questions.options.create', $question) }}'">New Option</x-primary-button>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($options->isEmpty())
        <x-card class="text-center text-gray-500 dark:text-gray-400">
            No options yet for this question. Create the first one.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($options as $option)
                <x-card class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $option->label }}</p>
                            @unless ($option->is_published)
                                <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">Unpublished</span>
                            @endunless
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            @if ($option->price_modifier > 0)
                                +${{ number_format($option->price_modifier, 2) }}
                            @elseif ($option->price_modifier < 0)
                                -${{ number_format(abs($option->price_modifier), 2) }}
                            @else
                                Included
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('superadmin.options.edit', $option) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                </x-card>
            @endforeach
        </div>
    @endif
</x-superadmin-layout>
