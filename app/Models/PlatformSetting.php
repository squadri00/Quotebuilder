<?php

namespace App\Models;

use App\Models\Concerns\HasFormattedAddress;
use Illuminate\Database\Eloquent\Model;

/**
 * A single-row settings store for platform-level integration credentials
 * (Stripe, outgoing email) that the Super Admin manages from the UI
 * instead of editing .env directly. Values here, when present, override
 * the corresponding .env-driven config at boot — see
 * AppServiceProvider::boot(). Leaving a field blank falls back to .env,
 * so nothing breaks before these are ever filled in.
 *
 * Also holds the platform's own business profile (address/phone/email/
 * website/tax account info) — this is the platform operator's own
 * identity, not any tenant Business's. Nothing prints it automatically
 * today: subscription invoices are Stripe-hosted and branded from your
 * Stripe Dashboard's own Business settings, entirely outside this app's
 * control. This profile exists so any future platform-generated document
 * (e.g. an Implementation Service receipt) has a real identity to pull
 * from instead of needing one invented from scratch.
 */
class PlatformSetting extends Model
{
    use HasFormattedAddress;

    protected $fillable = [
        'platform_name',
        'legal_business_name',
        'logo_path',
        'dark_logo_path',
        'logo_display_style',
        'version',
        'address_line1',
        'address_line2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'phone',
        'contact_email',
        'website',
        'tax_account_info',
        'stripe_key',
        'stripe_secret',
        'stripe_webhook_secret',
        'default_currency',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_scheme',
        'mail_from_address',
        'mail_from_name',
        'turnstile_site_key',
        'turnstile_secret_key',
    ];

    protected function casts(): array
    {
        return [
            'stripe_secret' => 'encrypted',
            'stripe_webhook_secret' => 'encrypted',
            'mail_password' => 'encrypted',
            'mail_port' => 'integer',
            'turnstile_secret_key' => 'encrypted',
        ];
    }

    public static function get(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    /**
     * Currencies offered in the Super Admin dropdown — the ones Plans are
     * actually likely to be priced in, not Stripe's full ~135-currency list.
     */
    public static function currencyOptions(): array
    {
        return [
            'cad' => 'CAD — Canadian Dollar',
            'usd' => 'USD — US Dollar',
            'gbp' => 'GBP — British Pound',
            'eur' => 'EUR — Euro',
            'aud' => 'AUD — Australian Dollar',
        ];
    }
}
