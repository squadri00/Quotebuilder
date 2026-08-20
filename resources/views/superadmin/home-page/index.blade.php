<x-superadmin-layout title="Home Page">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Home Page</h2>
            <a href="{{ url('/') }}" target="_blank" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline">View live page</a>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-6" :status="session('status')" />

    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Hero Content</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">The headline block at the very top of the public <a href="{{ url('/') }}" target="_blank" class="underline hover:no-underline">Home page</a>.</p>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('superadmin.home-page.hero.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div>
                    <x-input-label for="heading" value="Headline" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Wrap part of the headline in <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">**double asterisks**</code> to make it green — for example <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">**Build Your Own** Quote Calculator</code>.
                    </p>
                    <textarea id="heading" name="heading" rows="2" required maxlength="200"
                        class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-semibold"
                    >{{ old('heading', $hero->heading) }}</textarea>
                    <x-input-error :messages="$errors->get('heading')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="subheading" value="Subheading" />
                    <textarea id="subheading" name="subheading" rows="4" required maxlength="600"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >{{ old('subheading', $hero->subheading) }}</textarea>
                    <x-input-error :messages="$errors->get('subheading')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6">
                <x-primary-button>Save Hero Content</x-primary-button>
            </div>
        </form>
    </x-card>
</x-superadmin-layout>
