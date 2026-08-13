<x-superadmin-layout :title="'New Product — '.$business->name">
    <x-slot name="header">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('superadmin.products.index', $business) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $business->name }}</a> / Quote Builder
            </p>
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Product</h2>
        </div>
    </x-slot>

    <x-auth-session-status class="max-w-xl mb-4" :status="session('status')" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.products.store', $business) }}">
            @csrf

            @include('products._form')

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Create Product</x-primary-button>
                <a href="{{ route('superadmin.products.index', $business) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
