@props(['card' => null])

<div class="space-y-5" x-data="{ icon: @js(old('icon', $card?->icon ?? \App\Support\MarketingFeatureIcons::paths()[0])) }">
    <div>
        <x-input-label for="title" value="Card Title" />
        <x-text-input id="title" name="title" type="text" class="block mt-1 w-full" required
            placeholder="e.g. No-code quote builder" :value="old('title', $card?->title)" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="body" value="Card Description" />
        <textarea id="body" name="body" rows="4" required maxlength="600"
            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="One or two sentences describing this feature.">{{ old('body', $card?->body) }}</textarea>
        <x-input-error :messages="$errors->get('body')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="icon" value="Icon" />
        <div class="mt-2 flex items-center gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" :d="icon" /></svg>
            </span>
            <select id="icon" name="icon" x-model="icon" required
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (\App\Support\MarketingFeatureIcons::options() as $label => $path)
                    <option value="{{ $path }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" value="Display Order" />
        <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="block mt-1 w-32"
            :value="old('sort_order', $card?->sort_order ?? 999)" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lower numbers show first.</p>
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('is_active', $card?->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-300">Live (shown on the public Features page)</span>
        </label>
    </div>
</div>
