<x-superadmin-layout :title="'Edit '.$template->name">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Template</h2>
            <x-primary-button onclick="window.location='{{ route('superadmin.products.index', $template) }}'">Quote Builder</x-primary-button>
        </div>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.templates.update', $template) }}">
            @csrf
            @method('PATCH')

            @include('superadmin.templates._form', ['template' => $template, 'industries' => $industries])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('superadmin.templates.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
