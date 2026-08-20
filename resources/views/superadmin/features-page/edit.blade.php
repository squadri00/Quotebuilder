<x-superadmin-layout title="Edit Feature Card">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit — {{ $card->title }}</h2>
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('superadmin.features-page.cards.update', $card) }}">
            @csrf
            @method('PUT')

            @include('superadmin.features-page._card-form', ['card' => $card])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('superadmin.features-page.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
