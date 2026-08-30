<?php

namespace App\Services\Affiliate;

use App\Models\PlatformSetting;

/**
 * Thin typed accessor over the referral-program columns on the
 * single-row platform_settings store.
 */
class AffiliateSettings
{
    public static function all(): PlatformSetting
    {
        return PlatformSetting::get();
    }

    public static function enabled(): bool
    {
        return (bool) static::all()->affiliate_program_enabled;
    }

    public static function defaultRate(): float
    {
        return (float) (static::all()->affiliate_default_commission_rate ?? 20);
    }

    public static function claimDays(): int
    {
        return max(1, (int) (static::all()->affiliate_claim_days ?? 30));
    }

    public static function claimRenewsOnActivity(): bool
    {
        return (bool) static::all()->affiliate_claim_renew_on_activity;
    }

    public static function cookieDays(): int
    {
        return max(1, (int) (static::all()->affiliate_cookie_days ?? 45));
    }

    public static function minPayout(): float
    {
        return (float) (static::all()->affiliate_min_payout ?? 50);
    }

    public static function autoApprovePartners(): bool
    {
        return (bool) static::all()->affiliate_auto_approve_partners;
    }

    public static function autoApproveReferrals(): bool
    {
        return (bool) static::all()->affiliate_auto_approve_referrals;
    }

    public static function payoutCurrency(): string
    {
        $c = strtoupper(trim((string) static::all()->affiliate_payout_currency));
        if ($c !== '') {
            return $c;
        }
        return strtoupper((string) (static::all()->default_currency ?? 'CAD')) ?: 'CAD';
    }

    public static function termsUrl(): ?string
    {
        return static::all()->affiliate_terms_url ?: null;
    }
}
