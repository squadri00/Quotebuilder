<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">New Support Ticket</h2>
    </x-slot>

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('support.store') }}">
            @csrf

            <div>
                <x-input-label for="subject" value="Subject" />
                <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" required autofocus
                    :value="old('subject')" />
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="message" value="Message" />
                <textarea id="message" name="message" rows="6" required
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">{{ old('message') }}</textarea>
                <x-input-error :messages="$errors->get('message')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Submit Ticket</x-primary-button>
                <a href="{{ route('support.index') }}" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </x-card>
</x-app-layout>
