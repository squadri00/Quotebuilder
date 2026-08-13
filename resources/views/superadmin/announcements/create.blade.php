<x-superadmin-layout title="New Announcement">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Announcement</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.announcements.store') }}">
            @csrf

            @include('superadmin.announcements._form', ['businesses' => $businesses, 'audienceCounts' => $audienceCounts])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Create Announcement</x-primary-button>
                <a href="{{ route('superadmin.announcements.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
