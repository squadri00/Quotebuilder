<x-superadmin-layout title="New Social Link">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Social Link</h2>
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('superadmin.social-links.store') }}">
            @csrf

            @include('superadmin.social-links._form')

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Add Social Link</x-primary-button>
                <a href="{{ route('superadmin.social-links.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
