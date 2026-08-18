<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <div class="space-y-6">
            <x-card>
                @include('profile.partials.update-profile-information-form')
            </x-card>

            <x-card>
                @include('profile.partials.update-password-form')
            </x-card>

            @if (Auth::user()->canManageBusinessSettings())
                <x-card>
                    @include('profile.partials.update-business-address-form', ['business' => Auth::user()->business])
                </x-card>

                <x-card>
                    @include('profile.partials.update-business-branding-form', ['business' => Auth::user()->business])
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            @if (Auth::user()->canManageBusinessSettings())
                <x-card>
                    @include('profile.partials.update-public-quote-settings-form', ['business' => Auth::user()->business])
                </x-card>

                <x-card>
                    @include('profile.partials.update-quote-numbering-form', ['business' => Auth::user()->business])
                </x-card>

                <x-card>
                    @include('profile.partials.tax-rates-summary', ['taxRateCount' => Auth::user()->business->shopTaxRates()->count()])
                </x-card>
            @endif

            <x-card>
                @include('profile.partials.business-qr-code', ['business' => Auth::user()->business])
            </x-card>
        </div>
    </div>

    <div class="mt-6">
        <x-card>
            @include('profile.partials.delete-user-form')
        </x-card>
    </div>
</x-app-layout>
