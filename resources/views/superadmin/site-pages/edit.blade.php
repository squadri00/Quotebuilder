<x-superadmin-layout title="Edit Site Page">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit — {{ $sitePage->title }}</h2>
            <x-secondary-button onclick="window.open('{{ route('legal.show', $sitePage->slug) }}', '_blank')">View Live</x-secondary-button>
        </div>
    </x-slot>

    <p class="max-w-3xl text-sm text-gray-500 dark:text-gray-400 mb-4">
        Currently shows <strong>Last updated {{ $sitePage->updated_at->format('F j, Y') }}</strong> on the public page. Saving below updates that date to today.
    </p>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('superadmin.site-pages.update', $sitePage) }}">
            @csrf
            @method('PUT')

            @include('superadmin.site-pages._form', ['sitePage' => $sitePage])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('superadmin.site-pages.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
