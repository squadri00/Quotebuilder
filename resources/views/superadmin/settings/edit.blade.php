<x-superadmin-layout title="Platform Settings">
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100">Platform Settings</h2>
    </x-slot>

    <p class="max-w-3xl mb-4 text-sm text-gray-500 dark:text-gray-400">
        Stripe and outgoing-email credentials for the whole platform. Anything saved here overrides the server's .env file — leave a field blank to keep whatever .env already provides. Secret fields are never shown again once saved; leave them blank when editing to keep the current value.
    </p>

    <x-auth-session-status class="max-w-3xl mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('superadmin.settings.update') }}" enctype="multipart/form-data" class="max-w-6xl">
        @csrf
        @method('PATCH')

        <div class="columns-1 lg:columns-2 gap-6 [column-fill:_balance]">

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Branding</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">The product name, logo, and version shown across page titles, navigation, emails, the Super Admin area, and the customer portal.</p>

            <div>
                <x-input-label for="platform_name" value="Platform Name" />
                <x-text-input id="platform_name" name="platform_name" type="text" class="block mt-1 w-full"
                    placeholder="{{ config('app.name') }}" :value="old('platform_name', $settings->platform_name)" />
                <x-input-error :messages="$errors->get('platform_name')" class="mt-2" />
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <x-input-label value="Light Mode Logo" />
                    <div class="mt-2 flex items-center gap-3">
                        @if ($settings->logo_path)
                            <img src="{{ Storage::url($settings->logo_path) }}" alt="{{ $settings->platform_name ?? config('app.name') }}" class="h-10 max-w-[140px] object-contain rounded-lg border border-gray-200 bg-white p-1">
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">No logo uploaded yet — a plain letter mark is shown until one is set.</p>
                        @endif
                    </div>
                    <input id="logo" name="logo" type="file" accept="image/*"
                        class="mt-3 block w-full text-xs text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200">
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <x-input-label value="Dark Mode Logo" />
                    <div class="mt-2 flex items-center gap-3">
                        @if ($settings->dark_logo_path)
                            <img src="{{ Storage::url($settings->dark_logo_path) }}" alt="{{ $settings->platform_name ?? config('app.name') }}" class="h-10 max-w-[140px] object-contain rounded-lg border border-gray-700 bg-gray-900 p-1">
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">Not set — the light mode logo is used in dark mode too until one is uploaded here.</p>
                        @endif
                    </div>
                    <input id="dark_logo" name="dark_logo" type="file" accept="image/*"
                        class="mt-3 block w-full text-xs text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200">
                    <x-input-error :messages="$errors->get('dark_logo')" class="mt-2" />
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, or SVG — max 2MB each. Shown top-left in both the Super Admin sidebar and the customer portal sidebar, sized to fit — pick whichever version reads best against a light or dark background.</p>

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

        <x-card class="break-inside-avoid mb-6 {{ $settings->maintenance_mode ? 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30' : '' }}">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Maintenance Mode</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                When on, nobody can log in, sign up, or purchase/change anything — everywhere that touches Login, Register, Checkout, or Billing shows a "temporarily unavailable" page with the message below instead. <strong>Everything else keeps working as normal</strong>: the marketing site, every public quote page and embedded widget, and anyone already logged into a business dashboard. <strong>Super Admin is never affected</strong> — you can still log in and turn this back off at any time.
            </p>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="maintenance_mode" value="1" class="rounded border-gray-300 brand-checkbox shadow-sm focus:ring-indigo-500"
                    @checked(old('maintenance_mode', $settings->maintenance_mode))>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Turn on maintenance mode now</span>
            </label>
            @if ($settings->maintenance_mode)
                <p class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400">Currently ON — the site is showing the maintenance page to everyone except Super Admin.</p>
            @endif

            <div class="mt-4">
                <x-input-label for="maintenance_message" value="Message shown to visitors" />
                <textarea id="maintenance_message" name="maintenance_message" rows="3"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                    placeholder="We're currently performing scheduled maintenance. Please check back soon.">{{ old('maintenance_message', $settings->maintenance_message) }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to use the default message shown above as a placeholder.</p>
                <x-input-error :messages="$errors->get('maintenance_message')" class="mt-2" />
            </div>
        </x-card>

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Spam Protection</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Cloudflare Turnstile — a free, usually-invisible "prove you're not a robot" check shown on public quote forms, on top of the honeypot field that's always on. Get a site key and secret key at <span class="font-mono">dash.cloudflare.com</span> → Turnstile (free, no card required). Leave both blank to leave it off — every submission passes straight through, same as today.
            </p>

            <div>
                <x-input-label for="turnstile_site_key" value="Site Key" />
                <x-text-input id="turnstile_site_key" name="turnstile_site_key" type="text" class="block mt-1 w-full"
                    placeholder="0x4AAAAAAA..." :value="old('turnstile_site_key', $settings->turnstile_site_key)" />
                <x-input-error :messages="$errors->get('turnstile_site_key')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="turnstile_secret_key" value="Secret Key" />
                <x-text-input id="turnstile_secret_key" name="turnstile_secret_key" type="password" class="block mt-1 w-full"
                    placeholder="{{ $settings->turnstile_secret_key ? '••••••••' : '' }}" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Never shown again once saved — leave blank to keep the current one.</p>
                <x-input-error :messages="$errors->get('turnstile_secret_key')" class="mt-2" />
            </div>
        </x-card>

        <x-card class="break-inside-avoid mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Business Profile</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Your own company's identity — not any business using the platform. Nothing prints this automatically today (Stripe hosts and brands subscription invoices from your own Stripe Dashboard settings, outside this app), but it's here ready for anything platform-generated that needs it later.
            </p>

            <div>
                <x-input-label for="legal_business_name" value="Legal Business Name" />
                <x-text-input id="legal_business_name" name="legal_business_name" type="text" class="block mt-1 w-full"
                    placeholder="e.g. Eformics Systems" :value="old('legal_business_name', $settings->legal_business_name)" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your full legal entity name — used in the site footer's copyright line and anywhere else the legal entity (not the "{{ $settings->platform_name ?: config('app.name') }}" brand name) needs to appear.</p>
                <x-input-error :messages="$errors->get('legal_business_name')" class="mt-2" />
            </div>

            <div class="mt-4">
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

        <x-card class="break-inside-avoid mb-6">
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

        <x-card class="break-inside-avoid mb-6">
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

        </div>

        <div class="mt-6">
            <x-primary-button>Save Changes</x-primary-button>
        </div>
    </form>
</x-superadmin-layout>
