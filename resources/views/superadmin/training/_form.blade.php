@php $artifact ??= null; $industries ??= collect(); @endphp

<div>
    <x-input-label for="title" :value="__('Title')" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $artifact?->title)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('title')" />
</div>

<div class="mt-6">
    <x-input-label for="industry_id" :value="__('Industry')" />
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Groups this artifact with other material on the same industry on the Training Library page — e.g. a Cabinet &amp; Millwork build sheet groups with other Cabinet &amp; Millwork references. Leave as "General" if it isn't about one specific industry.
    </p>
    <select id="industry_id" name="industry_id"
        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
        <option value="">General (not industry-specific)</option>
        @foreach ($industries as $industry)
            <option value="{{ $industry->id }}" @selected((string) old('industry_id', $artifact?->industry_id) === (string) $industry->id)>{{ $industry->name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('industry_id')" />
</div>

<div class="mt-6">
    <x-input-label for="html" :value="__('HTML')" />
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $artifact ? 'Paste replacement HTML to overwrite what\'s stored — leave blank to keep the current content.' : 'Paste the full HTML — e.g. copied straight from a Claude Artifact.' }}
    </p>
    <textarea id="html" name="html" rows="12" spellcheck="false"
        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm font-mono text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
        placeholder="<!doctype html>&#10;<html>&#10;  ...&#10;</html>"
    >{{ old('html') }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('html')" />
</div>

<div class="mt-6">
    <x-input-label for="html_file" :value="__('— or upload an .html file instead —')" />
    <input id="html_file" name="html_file" type="file" accept=".html,.htm,text/html"
        class="mt-2 block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200">
    <x-input-error class="mt-2" :messages="$errors->get('html_file')" />
</div>
