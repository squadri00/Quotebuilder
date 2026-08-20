<x-superadmin-layout title="New Site Page">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Site Page</h2>
    </x-slot>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('superadmin.site-pages.store') }}">
            @csrf

            @include('superadmin.site-pages._form')

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Add Page</x-primary-button>
                <a href="{{ route('superadmin.site-pages.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
