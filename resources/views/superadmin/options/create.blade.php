<x-superadmin-layout :title="'New Option — '.$question->question_text">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Option for {{ $question->question_text }}</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('superadmin.questions.options.store', $question) }}">
            @csrf

            @include('options._form')

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Create Option</x-primary-button>
                <a href="{{ route('superadmin.questions.options.index', $question) }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Cancel</a>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
