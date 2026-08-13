@props(['template' => null, 'industries' => collect()])

<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" required autofocus
        :value="old('name', $template?->name)" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="industry_id" value="Industry" />
    <select id="industry_id" name="industry_id"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
        <option value="">No industry</option>
        @foreach ($industries as $industry)
            <option value="{{ $industry->id }}" @selected((string) old('industry_id', $template?->industry_id) === (string) $industry->id)>
                {{ $industry->name }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        Used to recommend this template to a matching business at signup.
        @if ($industries->isEmpty())
            No industries exist yet — <a href="{{ route('superadmin.industries.create') }}" class="brand-text">create one first</a>.
        @endif
    </p>
    <x-input-error :messages="$errors->get('industry_id')" class="mt-2" />
</div>
