<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformSettingController extends Controller
{
    public function edit(): View
    {
        $settings = PlatformSetting::get();

        return view('superadmin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => ['nullable', 'string', 'max:255'],
            'legal_business_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'dark_logo' => ['nullable', 'image', 'max:2048'],
            'logo_display_style' => ['nullable', 'string', Rule::in(['icon', 'full'])],
            'turnstile_site_key' => ['nullable', 'string', 'max:255'],
            'turnstile_secret_key' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:50'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'tax_account_info' => ['nullable', 'string', 'max:255'],
            'stripe_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'default_currency' => ['nullable', 'string', Rule::in(array_keys(PlatformSetting::currencyOptions()))],
            'mail_mailer' => ['nullable', 'string', 'max:255'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_scheme' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'maintenance_message' => ['nullable', 'string', 'max:2000'],
            'head_code' => ['nullable', 'string', 'max:20000', $this->noPageStructure()],
            'body_code' => ['nullable', 'string', 'max:20000', $this->noPageStructure()],
            'custom_code_areas' => ['nullable', 'array'],
            'custom_code_areas.*' => ['string', Rule::in(array_keys(PlatformSetting::CODE_AREAS))],
        ]);

        $validated['maintenance_mode'] = $request->boolean('maintenance_mode');

        $settings = PlatformSetting::get();

        // The custom code box is only handled when its own part of the form was sent, so saving another part of the page
        // (or an older form) can never switch the code off or change where it goes.
        if ($request->boolean('custom_code_form')) {
            $validated['custom_code_enabled'] = $request->boolean('custom_code_enabled');
            $validated['custom_code_areas'] = array_values(array_unique($validated['custom_code_areas'] ?? []));
        } else {
            unset($validated['head_code'], $validated['body_code'], $validated['custom_code_areas']);
        }
        $codeChanged = $request->boolean('custom_code_form')
            && (($validated['head_code'] ?? null) !== $settings->head_code
                || ($validated['body_code'] ?? null) !== $settings->body_code
                || $validated['custom_code_enabled'] !== $settings->custom_code_enabled
                || $validated['custom_code_areas'] !== ($settings->custom_code_areas ?? PlatformSetting::DEFAULT_CODE_AREAS));

        // Secret fields are never redisplayed in the form, so a blank
        // submission means "leave it as it is" rather than "clear it" —
        // otherwise saving the form after just changing, say, the SMTP
        // host would silently wipe out an already-saved Stripe secret.
        foreach (['stripe_secret', 'stripe_webhook_secret', 'mail_password', 'turnstile_secret_key'] as $secretField) {
            if (empty($validated[$secretField])) {
                unset($validated[$secretField]);
            }
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('platform', 'public');
        }

        if ($request->hasFile('dark_logo')) {
            if ($settings->dark_logo_path) {
                Storage::disk('public')->delete($settings->dark_logo_path);
            }

            $validated['dark_logo_path'] = $request->file('dark_logo')->store('platform', 'public');
        }

        unset($validated['logo'], $validated['dark_logo']);

        $settings->update($validated);

        if ($codeChanged) {
            AuditLog::record(
                $request->user('admin'),
                'platform.custom_code',
                $settings,
                'Changed the custom head/body code ('.($settings->custom_code_enabled ? 'on' : 'off').', areas: '.implode(', ', $settings->custom_code_areas ?? []).').'
            );
        }

        return redirect()->route('superadmin.settings.edit')->with('status', 'Platform settings updated.');
    }

    // Code for the head or the body must not close or reopen the page itself: that would break every page it is added to.
    private function noPageStructure(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && preg_match('~</?\s*(html|head|body)\b~i', $value)) {
                $fail('Leave out the html, head and body tags themselves: paste only what goes inside (for example a meta tag or a script).');
            }
        };
    }
}
