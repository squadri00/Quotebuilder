@unless ($business->hasFeature('custom_branding'))
    <p class="pb-6 text-center text-xs text-gray-400 dark:text-gray-500">
        Powered by <span class="font-semibold text-gray-500 dark:text-gray-400">{{ config('app.name') }}</span>
    </p>
@endunless
