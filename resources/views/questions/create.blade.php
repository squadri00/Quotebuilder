<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Question for {{ $product->name }}</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('products.questions.store', $product) }}">
            @csrf

            @include('questions._form')

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Create Question</x-primary-button>
                <a href="{{ route('products.questions.index', $product) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
