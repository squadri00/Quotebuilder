@props(['socialLink' => null])

<div class="space-y-5" x-data="{ platform: @js(old('platform', $socialLink?->platform ?? \App\Support\SocialPlatforms::keys()[0])), icons: @js(\App\Support\SocialPlatforms::forJs()) }">
    <div>
        <x-input-label for="platform" value="Platform" />
        <div class="mt-2 flex items-center gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" x-effect="$el.setAttribute('viewBox', icons[platform].viewBox)" fill="currentColor"><path :d="icons[platform].path" /></svg>
            </span>
            <select id="platform" name="platform" x-model="platform" required
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (\App\Support\SocialPlatforms::options() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <x-input-error :messages="$errors->get('platform')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="label" value="Custom Label" />
        <x-text-input id="label" name="label" type="text" class="block mt-1 w-full" maxlength="50"
            placeholder="e.g. Follow us on Threads" :value="old('label', $socialLink?->label)" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="platform === 'other'">Required when the platform is "Other" — shown as the icon's tooltip/alt text.</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="platform !== 'other'">Optional. Leave blank to just use the platform name.</p>
        <x-input-error :messages="$errors->get('label')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="url" value="Link URL" />
        <x-text-input id="url" name="url" type="url" class="block mt-1 w-full" required
            placeholder="https://facebook.com/yourbusiness" :value="old('url', $socialLink?->url)" />
        <x-input-error :messages="$errors->get('url')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" value="Display Order" />
        <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="block mt-1 w-32"
            :value="old('sort_order', $socialLink?->sort_order ?? 999)" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lower numbers show first.</p>
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('is_active', $socialLink?->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-300">Live (shown in the site footer)</span>
        </label>
    </div>
</div>
