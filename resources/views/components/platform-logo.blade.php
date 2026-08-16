@props(['settings', 'imgClass'])

{{--
    Renders the platform's own logo (Super Admin Settings > Branding),
    swapping instantly between light/dark variants via Tailwind's dark:
    classes — no reload needed, matching how the rest of the app's dark
    mode toggle already works. Falls back to a plain letter mark when
    nothing's been uploaded yet, and to the light logo in dark mode when
    only a light logo has been set.
--}}
@if ($settings?->logo_path)
    <img src="{{ Storage::url($settings->logo_path) }}" alt="{{ config('app.name') }}"
        class="{{ $imgClass }} {{ $settings->dark_logo_path ? 'dark:hidden' : '' }}">
    @if ($settings->dark_logo_path)
        <img src="{{ Storage::url($settings->dark_logo_path) }}" alt="{{ config('app.name') }}"
            class="hidden dark:block {{ $imgClass }}">
    @endif
@else
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-base font-bold text-white">{{ Str::upper(Str::substr(config('app.name'), 0, 1)) }}</span>
@endif
