<x-superadmin-layout title="Edit Training Artifact">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Training Artifact</h2>
    </x-slot>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('superadmin.training.update', $artifact) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            @include('superadmin.training._form')

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save</x-primary-button>
                <a href="{{ route('superadmin.training.show', $artifact) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
