<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateClick;
use App\Models\AffiliatePartner;
use App\Models\AffiliateProspect;
use App\Models\AffiliateReferral;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class AttributionService
{
    public const COOKIE = 'aff_ref';

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    }

    public function partnerByCode(?string $code): ?AffiliatePartner
    {
        $code = static::normalizeCode($code);
        return $code === '' ? null : AffiliatePartner::where('partner_code', $code)->first();
    }

    public function logClick(string $code, Request $request): void
    {
        $code = static::normalizeCode($code);
        if ($code === '') {
            return;
        }
        $ip = $request->ip();

        AffiliateClick::create([
            'partner_id' => optional($this->partnerByCode($code))->id,
            'partner_code' => $code,
            'ip_hash' => $ip ? hash('sha256', $ip) : null,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
            'landing_path' => mb_substr($request->path(), 0, 255),
            'referrer' => mb_substr((string) $request->headers->get('referer'), 0, 255) ?: null,
        ]);
    }

    public function cookieForCode(string $code): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make(
            static::COOKIE,
            static::normalizeCode($code),
            AffiliateSettings::cookieDays() * 24 * 60
        );
    }

    public function codeFromRequest(Request $request): string
    {
        return static::normalizeCode($request->cookie(static::COOKIE));
    }

    /**
     * Attribute a freshly-created business to a partner. Idempotent — a
     * business carries at most one referral. Returns the referral or null.
     */
    public function attach(Business $business, ?string $code): ?AffiliateReferral
    {
        if (! AffiliateSettings::enabled()) {
            return null;
        }

        if ($existing = AffiliateReferral::where('business_id', $business->id)->first()) {
            return $existing;
        }

        $partner = $this->partnerByCode($code);
        if (! $partner || ! $partner->isActive()) {
            return null;
        }

        $prospect = $this->matchOpenProspect($partner->id, $business);
        $autoApprove = AffiliateSettings::autoApproveReferrals();

        $referral = AffiliateReferral::create([
            'partner_id' => $partner->id,
            'business_id' => $business->id,
            'prospect_id' => $prospect?->id,
            'source' => $prospect ? 'prospect' : 'link',
            'status' => $autoApprove ? 'approved' : 'pending',
            'approved_at' => $autoApprove ? now() : null,
        ]);

        if ($prospect) {
            $prospect->update([
                'status' => 'won',
                'converted_business_id' => $business->id,
                'converted_at' => now(),
                'last_activity_at' => now(),
            ]);
        }

        AffiliateClick::where('partner_code', $partner->partner_code)
            ->whereNull('converted_business_id')
            ->latest()->limit(1)
            ->update(['converted_business_id' => $business->id]);

        Log::info("Affiliate: business #{$business->id} attributed to {$partner->partner_code} ("
            . ($autoApprove ? 'auto-approved' : 'pending approval') . ').');

        app(CommissionService::class)->accrueForBusiness($business->id);

        return $referral;
    }

    /**
     * @return array{ok:bool, referral?:AffiliateReferral, error?:string}
     */
    public function attachManual(int $businessId, int $partnerId, ?float $rate, bool $approve, ?int $adminId, ?string $note = null): array
    {
        if (AffiliateReferral::where('business_id', $businessId)->exists()) {
            return ['ok' => false, 'error' => 'That business already has a referring partner.'];
        }
        if (! AffiliatePartner::whereKey($partnerId)->exists()) {
            return ['ok' => false, 'error' => 'Partner not found.'];
        }

        $referral = AffiliateReferral::create([
            'partner_id' => $partnerId,
            'business_id' => $businessId,
            'source' => 'manual',
            'commission_rate' => $rate,
            'status' => $approve ? 'approved' : 'pending',
            'approved_at' => $approve ? now() : null,
            'approved_by' => $approve ? $adminId : null,
            'notes' => $note,
        ]);

        app(CommissionService::class)->accrueForBusiness($businessId);

        return ['ok' => true, 'referral' => $referral];
    }

    private function matchOpenProspect(int $partnerId, Business $business): ?AffiliateProspect
    {
        $keys = Normalizer::keys([
            'company_name' => $business->name,
            'email' => $business->notification_email,
            'phone' => $business->phone,
            'website' => null,
        ]);

        $query = AffiliateProspect::where('partner_id', $partnerId)
            ->whereIn('status', ['open', 'working'])
            ->whereNull('converted_business_id');

        $hasKey = false;
        $query->where(function ($q) use ($keys, &$hasKey) {
            foreach (['company_name_norm', 'phone_norm', 'email_norm', 'domain_norm'] as $k) {
                if (! empty($keys[$k])) {
                    $hasKey = true;
                    $q->orWhere($k, $keys[$k]);
                }
            }
        });

        return $hasKey ? $query->orderBy('claimed_at')->first() : null;
    }
}
