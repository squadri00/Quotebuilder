<x-superadmin-layout title="New Template">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Template</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.templates.store') }}">
            @csrf

            @include('superadmin.templates._form', ['industries' => $industries])

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                After creating the template, you'll land straight in its Quote Builder to add the first sample product.
            </p>

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Create Template</x-primary-button>
                <a href="{{ route('superadmin.templates.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
