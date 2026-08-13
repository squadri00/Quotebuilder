<x-superadmin-layout :title="'Edit '.$feature->name">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Feature</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.features.update', $feature) }}">
            @csrf
            @method('PATCH')

            @include('superadmin.features._form', ['feature' => $feature])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('superadmin.features.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
