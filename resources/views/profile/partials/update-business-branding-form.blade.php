<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Branding') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Your logo and brand color.') }}
        </p>
    </header>

    <form method="post" action="{{ route('business.branding.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label :value="__('Current Logo')" />
            <div class="mt-2 flex items-center gap-4">
                @if ($business->logo_path)
                    <img src="{{ Storage::url($business->logo_path) }}" alt="{{ $business->name }}" class="h-12 max-w-[160px] object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-1">
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No logo uploaded yet.') }}</p>
                @endif
            </div>
        </div>

        <div>
            <x-input-label for="logo" :value="__('Upload New Logo')" />
            <input id="logo" name="logo" type="file" accept="image/*"
                class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('JPG, PNG, or SVG — max 2MB.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('logo')" />
        </div>

        <div>
            <x-input-label for="brand_color" :value="__('Brand Color')" />
            <div class="mt-1 flex items-center gap-3">
                <input
                    type="color"
                    id="brand_color_picker"
                    value="{{ old('brand_color', $business->brand_color ?? '#4f46e5') }}"
                    class="h-10 w-14 shrink-0 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer bg-transparent"
                    oninput="document.getElementById('brand_color').value = this.value; document.getElementById('brand_preview').style.backgroundColor = this.value;"
                >
                <x-text-input
                    id="brand_color"
                    name="brand_color"
                    type="text"
                    class="block w-40"
                    :value="old('brand_color', $business->brand_color ?? '#4f46e5')"
                    oninput="document.getElementById('brand_color_picker').value = this.value; document.getElementById('brand_preview').style.backgroundColor = this.value;"
                />
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Used on quotes and customer-facing pages.') }}</span>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('brand_color')" />
        </div>

        <div>
            <x-input-label :value="__('Preview')" />
            <div id="brand_preview" class="mt-2 rounded-lg px-4 py-3 text-sm font-semibold text-white" style="background-color: {{ old('brand_color', $business->brand_color ?? '#4f46e5') }};">
                {{ $business->name }}
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Branding') }}</x-primary-button>

            @if (session('status') === 'business-branding-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
