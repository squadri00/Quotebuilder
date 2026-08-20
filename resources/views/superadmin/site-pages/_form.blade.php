@props(['sitePage' => null])

@php
    $slugLocked = $sitePage && in_array($sitePage->slug, ['privacy-policy', 'terms-of-use']);
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="title" value="Page Title" />
            <x-text-input id="title" name="title" type="text" class="block mt-1 w-full" required
                placeholder="e.g. Privacy Policy" :value="old('title', $sitePage?->title)" />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="slug" value="URL Slug" />
            <x-text-input id="slug" name="slug" type="text" class="block mt-1 w-full font-mono text-sm" required
                placeholder="e.g. privacy-policy" :value="old('slug', $sitePage?->slug)" :disabled="$slugLocked" />
            @if ($slugLocked)
                <input type="hidden" name="slug" value="{{ $sitePage->slug }}">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This slug is linked from the site footer, so it can't be changed here.</p>
            @else
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lowercase letters, numbers, and hyphens only. This becomes /legal/{{ '{slug}' }}.</p>
            @endif
            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="content" value="Content" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Plain text. Leave a blank line between paragraphs. Start a line with <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">## </code> to make it a section heading — for example <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">## Contact Us</code>.
        </p>
        <textarea id="content" name="content" rows="24" required
            class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
        >{{ old('content', $sitePage?->content) }}</textarea>
        <x-input-error :messages="$errors->get('content')" class="mt-2" />
    </div>
</div>
