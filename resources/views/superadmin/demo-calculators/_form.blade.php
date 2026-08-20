@props(['demoCalculator' => null, 'businesses' => collect()])

<div class="space-y-5" x-data="{ icon: @js(old('icon', $demoCalculator?->icon ?? \App\Support\DemoCalculatorIcons::paths()[0])) }">
    <div>
        <x-input-label for="business_id" value="Which business's calculator does this open?" />
        @if ($businesses->isEmpty())
            <p class="mt-2 text-sm text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2">
                No template businesses yet — create one first from Businesses or Templates, then come back here.
            </p>
        @else
            <select id="business_id" name="business_id" required
                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Select a business —</option>
                @foreach ($businesses as $business)
                    <option value="{{ $business->id }}" @selected(old('business_id', $demoCalculator?->business_id) == $business->id)>{{ $business->name }}</option>
                @endforeach
            </select>
        @endif
        <x-input-error :messages="$errors->get('business_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" value="Card Title" />
        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required
            placeholder="e.g. Printing & Signage" :value="old('name', $demoCalculator?->name)" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Card Description" />
        <textarea id="description" name="description" rows="3" required maxlength="500"
            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="One or two sentences about what this demo shows off.">{{ old('description', $demoCalculator?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="icon" value="Icon" />
        <div class="mt-2 flex items-center gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" :d="icon" /></svg>
            </span>
            <select id="icon" name="icon" x-model="icon" required
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (\App\Support\DemoCalculatorIcons::options() as $label => $path)
                    <option value="{{ $path }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" value="Display Order" />
        <x-text-input id="sort_order" name="sort_order" type="number" min="1" class="block mt-1 w-32"
            :value="old('sort_order', $demoCalculator?->sort_order)" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lower numbers show first. Leave blank to put it at the end.</p>
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('is_active', $demoCalculator?->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-300">Live (shown on the public Demo page)</span>
        </label>
    </div>
</div>
