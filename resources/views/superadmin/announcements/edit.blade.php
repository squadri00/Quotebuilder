<x-superadmin-layout :title="'Edit '.$announcement->title">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Announcement</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.announcements.update', $announcement) }}">
            @csrf
            @method('PUT')

            @include('superadmin.announcements._form', ['announcement' => $announcement, 'businesses' => $businesses, 'audienceCounts' => $audienceCounts])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('superadmin.announcements.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>

    <x-card class="max-w-xl mt-6">
        <form method="POST" action="{{ route('superadmin.announcements.destroy', $announcement) }}"
            onsubmit="return confirm('Delete this announcement? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <x-danger-button>Delete Announcement</x-danger-button>
        </form>
    </x-card>
</x-superadmin-layout>
