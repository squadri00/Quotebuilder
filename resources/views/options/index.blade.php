<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <a href="{{ route('products.index') }}" class="hover:text-gray-700">Products</a> /
                    <a href="{{ route('products.questions.index', $question->product_id) }}" class="hover:text-gray-700">{{ $question->product->name }}</a> /
                    {{ $question->question_text }}
                </p>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Options</h2>
            </div>
            <x-primary-button onclick="window.location='{{ route('questions.options.create', $question) }}'">New Option</x-primary-button>
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
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Unpublished</span>
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
                    <a href="{{ route('options.edit', $option) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900">Edit</a>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
