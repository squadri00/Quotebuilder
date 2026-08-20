<x-superadmin-layout title="Features Page">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Features Page</h2>
            <a href="{{ url('/features') }}" target="_blank" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline">View live page</a>
        </div>
    </x-slot>

    <x-auth-session-status class="mb-6" :status="session('status')" />

    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Hero Content</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">The headline block at the top of the public <a href="{{ url('/features') }}" target="_blank" class="underline hover:no-underline">Features page</a>.</p>

        <x-card class="max-w-3xl">
            <form method="POST" action="{{ route('superadmin.features-page.hero.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-5">
                    <div>
                        <x-input-label for="eyebrow_text" value="Eyebrow Badge (optional)" />
                        <x-text-input id="eyebrow_text" name="eyebrow_text" type="text" class="block mt-1 w-full" maxlength="80"
                            placeholder="e.g. Everything you need to quote faster" :value="old('eyebrow_text', $hero->eyebrow_text)" />
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The small pill above the headline. Leave blank to hide it.</p>
                        <x-input-error :messages="$errors->get('eyebrow_text')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="heading" value="Headline" />
                        <textarea id="heading" name="heading" rows="3" required maxlength="400"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-semibold"
                        >{{ old('heading', $hero->heading) }}</textarea>
                        <x-input-error :messages="$errors->get('heading')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="subheading" value="Subheading" />
                        <textarea id="subheading" name="subheading" rows="3" required maxlength="600"
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
    </div>

    <div>
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Feature Cards</h3>
            <x-primary-button onclick="window.location='{{ route('superadmin.features-page.cards.create') }}'">New Card</x-primary-button>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">The grid of cards below the hero. Only active cards show up, in the order below.</p>

        @if ($cards->isEmpty())
            <x-card class="text-center text-gray-500 dark:text-gray-400">
                No feature cards yet — add one to feature it on the public Features page.
            </x-card>
        @else
            <x-card class="p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Order</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Card</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($cards as $card)
                                <tr>
                                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500">{{ $card->sort_order }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card->icon }}" /></svg>
                                            </span>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $card->title }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md truncate">{{ $card->body }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $card->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $card->is_active ? 'Live' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-4">
                                            <form method="POST" action="{{ route('superadmin.features-page.cards.toggle-active', $card) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                                    {{ $card->is_active ? 'Hide' : 'Make live' }}
                                                </button>
                                            </form>
                                            <a href="{{ route('superadmin.features-page.cards.edit', $card) }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Edit</a>
                                            <form method="POST" action="{{ route('superadmin.features-page.cards.destroy', $card) }}"
                                                onsubmit="return confirm('Delete &quot;{{ $card->title }}&quot;?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    </div>
</x-superadmin-layout>
