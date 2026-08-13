<x-superadmin-layout title="New Implementation Tier">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Implementation Tier</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.implementation-tiers.store') }}">
            @csrf

            @include('superadmin.implementation-tiers._form')

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Create Tier</x-primary-button>
                <a href="{{ route('superadmin.implementation-tiers.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
