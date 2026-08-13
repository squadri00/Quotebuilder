@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 mb-1.5 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
</label>
