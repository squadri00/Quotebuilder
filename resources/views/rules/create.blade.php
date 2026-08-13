<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Rule</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('rules.store') }}">
            @csrf

            @include('rules._form', ['products' => $products])

            <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <x-primary-button>Create Rule</x-primary-button>
                <a href="{{ route('rules.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
