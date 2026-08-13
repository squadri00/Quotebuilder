@props(['industry' => null])

<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required autofocus
        placeholder="e.g. Printing" :value="old('name', $industry?->name)" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>
