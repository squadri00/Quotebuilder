<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Option</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('options.update', $option) }}">
            @csrf
            @method('PUT')

            @include('options._form', ['option' => $option])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('questions.options.index', $option->question_id) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Cancel</a>
            </div>
        </form>

        <form method="POST" action="{{ route('options.destroy', $option) }}" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"
            onsubmit="return confirm('Delete this option?');">
            @csrf
            @method('DELETE')
            <x-danger-button>Delete Option</x-danger-button>
        </form>
    </x-card>
</x-app-layout>
