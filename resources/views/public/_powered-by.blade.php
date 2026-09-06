@unless ($business->hasFeature('custom_branding'))
    <p class="pb-6 text-center text-xs text-gray-400 dark:text-gray-500">
        <a href="{{ url('/') }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center gap-1.5 align-middle hover:text-gray-600 dark:hover:text-gray-300">
            Powered by
            <x-platform-logo :settings="\App\Models\PlatformSetting::get()" img-class="h-4 w-auto object-contain" />
        </a>
    </p>
@endunless
