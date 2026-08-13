@props(['feature' => null])

<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required autofocus
        :value="old('name', $feature?->name)" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="key" value="Key" />
    <x-text-input id="key" name="key" type="text" class="block mt-1 w-full font-mono text-sm" required
        :value="old('key', $feature?->key)" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Used in code to check access, e.g. <code>pdf_download</code>. Letters, numbers, dashes, and underscores only.</p>
    <x-input-error :messages="$errors->get('key')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="Description" />
    <textarea id="description" name="description" rows="3"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">{{ old('description', $feature?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>
