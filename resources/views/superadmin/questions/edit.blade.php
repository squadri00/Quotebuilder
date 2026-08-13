<x-superadmin-layout title="Edit Question">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Edit Question</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.questions.update', $question) }}">
            @csrf
            @method('PUT')

            @include('questions._form', ['question' => $question])

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Save Changes</x-primary-button>
                @if ($question->type === 'single_choice')
                    <a href="{{ route('superadmin.questions.options.index', $question) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Manage Options</a>
                @endif
                <a href="{{ route('superadmin.products.questions.index', [$business, $question->product_id]) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>

        <form method="POST" action="{{ route('superadmin.questions.destroy', $question) }}" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"
            onsubmit="return confirm('Delete this question? This also deletes its options.');">
            @csrf
            @method('DELETE')
            <x-danger-button>Delete Question</x-danger-button>
        </form>
    </x-card>
</x-superadmin-layout>
