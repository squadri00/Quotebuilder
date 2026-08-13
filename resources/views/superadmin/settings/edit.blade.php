<x-superadmin-layout title="Platform Settings">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Platform Settings</h2>
    </x-slot>

    <p class="max-w-xl mb-4 text-sm text-gray-500 dark:text-gray-400">
        Stripe and outgoing-email credentials for the whole platform. Anything saved here overrides the server's .env file — leave a field blank to keep whatever .env already provides. Secret fields are never shown again once saved; leave them blank when editing to keep the current value.
    </p>

    <x-auth-session-status class="max-w-xl mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('superadmin.settings.update') }}" enctype="multipart/form-data" class="max-w-xl space-y-6">
        @csrf
        @method('PATCH')

        <x-card>
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Branding</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">The product name, logo, and version shown across page titles, navigation, emails, the Super Admin area, and the customer portal.</p>

            <div>
                <x-input-label for="platform_name" value="Platform Name" />
                <x-text-input id="platform_name" name="platform_name" type="text" class="block mt-1 w-full"
                    placeholder="{{ config('app.name') }}" :value="old('platform_name', $settings->platform_name)" />
                <x-input-error :messages="$errors->get('platform_name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label value="Current Logo" />
                <div class="mt-2 flex items-center gap-4">
                    @if ($settings->logo_path)
                        <img src="{{ Storage::url($settings->logo_path) }}" alt="{{ $settings->platform_name ?? config('app.name') }}" class="h-10 max-w-[160px] object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-1">
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No logo uploaded yet — the sidebar shows a plain letter mark until one is set.</p>
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="logo" value="Upload New Logo" />
                <input id="logo" name="logo" type="file" accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, or SVG — max 2MB. Shown top-left in both the Super Admin sidebar and the customer portal sidebar, sized to fit.</p>
                <x-input-error :messages="$errors->get('logo')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="logo_display_style" value="Logo Display Style" />
                <select id="logo_display_style" name="logo_display_style"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="icon" @selected(old('logo_display_style', $settings->logo_display_style) === 'icon')>Small icon + platform name</option>
                    <option value="full" @selected(old('logo_display_style', $settings->logo_display_style) === 'full')>Full-width logo, no text</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">How the logo appears top-left in the Super Admin and customer portal sidebars.</p>
                <x-input-error :messages="$errors->get('logo_display_style')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="version" value="Version Number" />
                <x-text-input id="version" name="version" type="text" class="block mt-1 w-full"
                    placeholder="e.g. 1.0.0" :value="old('version', $settings->version)" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional — shown under the logo in the Super Admin sidebar.</p>
                <x-input-error :messages="$errors->get('version')" class="mt-2" />
            </div>
        </x-card>

        <x-card>
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Business Profile</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Your own company's identity — not any business using the platform. Nothing prints this automatically today (Stripe hosts and brands subscription invoices from your own Stripe Dashboard settings, outside this app), but it's here ready for anything platform-generated that needs it later.
            </p>

            <div>
                <x-input-label for="address_line1" value="Address Line 1" />
                <x-text-input id="address_line1" name="address_line1" type="text" class="block mt-1 w-full" :value="old('address_line1', $settings->address_line1)" />
                <x-input-error :messages="$errors->get('address_line1')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="address_line2" value="Address Line 2" />
                <x-text-input id="address_line2" name="address_line2" type="text" class="block mt-1 w-full" :value="old('address_line2', $settings->address_line2)" />
                <x-input-error :messages="$errors->get('address_line2')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="city" value="City" />
                    <x-text-input id="city" name="city" type="text" class="block mt-1 w-full" :value="old('city', $settings->city)" />
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="state_province" value="State / Province" />
                    <x-text-input id="state_province" name="state_province" type="text" class="block mt-1 w-full" :value="old('state_province', $settings->state_province)" />
                    <x-input-error :messages="$errors->get('state_province')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="postal_code" value="Postal Code" />
                    <x-text-input id="postal_code" name="postal_code" type="text" class="block mt-1 w-full" :value="old('postal_code', $settings->postal_code)" />
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="country" value="Country" />
                    <x-text-input id="country" name="country" type="text" class="block mt-1 w-full" :value="old('country', $settings->country)" />
                    <x-input-error :messages="$errors->get('country')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full" :value="old('phone', $settings->phone)" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="contact_email" value="Contact Email" />
                    <x-text-input id="contact_email" name="contact_email" type="email" class="block mt-1 w-full" :value="old('contact_email', $settings->contact_email)" />
                    <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="website" value="Website" />
                <x-text-input id="website" name="website" type="text" class="block mt-1 w-full" placeholder="https://yourdomain.com" :value="old('website', $settings->website)" />
                <x-input-error :messages="$errors->get('website')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="tax_account_info" value="Tax ID / Registration Number" />
                <x-text-input id="tax_account_info" name="tax_account_info" type="text" class="block mt-1 w-full" placeholder="e.g. GST/HST number, VAT number, EIN" :value="old('tax_account_info', $settings->tax_account_info)" />
                <x-input-error :messages="$errors->get('tax_account_info')" class="mt-2" />
            </div>
        </x-card>

        <x-card>
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Stripe</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">From your Stripe Dashboard → Developers → API keys (and Webhooks, for the signing secret).</p>

            <div>
                <x-input-label for="stripe_key" value="Publishable Key" />
                <x-text-input id="stripe_key" name="stripe_key" type="text" class="block mt-1 w-full font-mono text-sm"
                    placeholder="pk_test_... or pk_live_..." :value="old('stripe_key', $settings->stripe_key)" />
                <x-input-error :messages="$errors->get('stripe_key')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="stripe_secret" value="Secret Key" />
                <x-text-input id="stripe_secret" name="stripe_secret" type="password" class="block mt-1 w-full font-mono text-sm"
                    placeholder="{{ $settings->stripe_secret ? 'Already set — leave blank to keep it' : 'sk_test_... or sk_live_...' }}" />
                <x-input-error :messages="$errors->get('stripe_secret')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="stripe_webhook_secret" value="Webhook Signing Secret" />
                <x-text-input id="stripe_webhook_secret" name="stripe_webhook_secret" type="password" class="block mt-1 w-full font-mono text-sm"
                    placeholder="{{ $settings->stripe_webhook_secret ? 'Already set — leave blank to keep it' : 'whsec_...' }}" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">From the specific webhook endpoint you've registered in Stripe pointing at this site.</p>
                <x-input-error :messages="$errors->get('stripe_webhook_secret')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="default_currency" value="Default Currency" />
                <select id="default_currency" name="default_currency"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
                    <option value="">— Use .env default (currently "{{ strtoupper(config('cashier.currency')) }}") —</option>
                    @foreach (\App\Models\PlatformSetting::currencyOptions() as $code => $label)
                        <option value="{{ $code }}" @selected(old('default_currency', $settings->default_currency) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Every Plan's Stripe Price must be created in this same currency — a subscription can never switch currency after it's created, so changing this only affects <em>new</em> subscriptions going forward.
                </p>
                <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
            </div>
        </x-card>

        <x-card>
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Outgoing Email (SMTP)</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                For Brevo: host <code class="text-xs">smtp-relay.brevo.com</code>, port <code class="text-xs">587</code>, username is your Brevo account email, password is your SMTP key (not your account password) — leave Encryption blank.
            </p>

            <div>
                <x-input-label for="mail_mailer" value="Mailer" />
                <select id="mail_mailer" name="mail_mailer"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm">
                    <option value="">— Use .env default —</option>
                    <option value="smtp" @selected(old('mail_mailer', $settings->mail_mailer) === 'smtp')>SMTP</option>
                    <option value="log" @selected(old('mail_mailer', $settings->mail_mailer) === 'log')>Log only (testing — no real emails sent)</option>
                </select>
                <x-input-error :messages="$errors->get('mail_mailer')" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="mail_host" value="SMTP Host" />
                    <x-text-input id="mail_host" name="mail_host" type="text" class="block mt-1 w-full"
                        placeholder="smtp-relay.brevo.com" :value="old('mail_host', $settings->mail_host)" />
                    <x-input-error :messages="$errors->get('mail_host')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="mail_port" value="Port" />
                    <x-text-input id="mail_port" name="mail_port" type="number" class="block mt-1 w-full"
                        placeholder="587" :value="old('mail_port', $settings->mail_port)" />
                    <x-input-error :messages="$errors->get('mail_port')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="mail_username" value="SMTP Username" />
                <x-text-input id="mail_username" name="mail_username" type="text" class="block mt-1 w-full"
                    :value="old('mail_username', $settings->mail_username)" />
                <x-input-error :messages="$errors->get('mail_username')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="mail_password" value="SMTP Password" />
                <x-text-input id="mail_password" name="mail_password" type="password" class="block mt-1 w-full"
                    placeholder="{{ $settings->mail_password ? 'Already set — leave blank to keep it' : '' }}" />
                <x-input-error :messages="$errors->get('mail_password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="mail_scheme" value="Encryption (optional)" />
                <x-text-input id="mail_scheme" name="mail_scheme" type="text" class="block mt-1 w-full"
                    placeholder="Leave blank for STARTTLS, or 'smtps' for implicit TLS (port 465)" :value="old('mail_scheme', $settings->mail_scheme)" />
                <x-input-error :messages="$errors->get('mail_scheme')" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="mail_from_address" value="From Address" />
                    <x-text-input id="mail_from_address" name="mail_from_address" type="email" class="block mt-1 w-full"
                        placeholder="hello@yourdomain.com" :value="old('mail_from_address', $settings->mail_from_address)" />
                    <x-input-error :messages="$errors->get('mail_from_address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="mail_from_name" value="From Name" />
                    <x-text-input id="mail_from_name" name="mail_from_name" type="text" class="block mt-1 w-full"
                        placeholder="QuoteBuilder" :value="old('mail_from_name', $settings->mail_from_name)" />
                    <x-input-error :messages="$errors->get('mail_from_name')" class="mt-2" />
                </div>
            </div>
        </x-card>

        <x-primary-button>Save Changes</x-primary-button>
    </form>
</x-superadmin-layout>
