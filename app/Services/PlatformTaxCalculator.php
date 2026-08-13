<?php

namespace App\Services;

use App\Models\Business;
use App\Models\PlatformTaxRate;
use App\Support\ProvinceCodes;

/**
 * The tax QuoteBuilder itself (the platform) charges a business for its
 * own subscription/purchases, based on where that business is located —
 * entirely separate from TaxCalculator, which is the tax a business
 * charges its own customers on quotes.
 *
 * Mirrors Meccora's platform-tax.php: a Canadian home base charging a
 * higher combined rate to its own province, a lower federal-only rate to
 * the rest of Canada, and nothing to anyone outside Canada.
 */
class PlatformTaxCalculator
{
    private const HOME_COUNTRY_CODES = ['CA', 'CANADA'];

    /**
     * The rate that applies to this business, or null if none does
     * (outside Canada, or an unrecognized/blank province with no
     * country-wide default configured).
     */
    public function rateFor(Business $business): ?PlatformTaxRate
    {
        if (! $this->isHomeCountry($business->country)) {
            return null;
        }

        $province = $this->normalizeProvince($business->state_province);

        if ($province) {
            $exact = PlatformTaxRate::where('country_code', 'CA')
                ->where('province', $province)
                ->where('is_active', true)
                ->first();

            if ($exact) {
                return $exact;
            }
        }

        return PlatformTaxRate::where('country_code', 'CA')
            ->whereNull('province')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Display-only: splits an already-fixed charge (e.g. a subscription's
     * one Stripe price, the same for every business) backward into a
     * base/tax breakdown for informational display. Never changes what's
     * actually charged.
     *
     * @return array{base: float, tax: float, total: float, label: ?string, rate: float}
     */
    public function splitFromTotal(Business $business, float $total): array
    {
        $rate = $this->rateFor($business);

        if (! $rate) {
            return ['base' => round($total, 2), 'tax' => 0.0, 'total' => round($total, 2), 'label' => null, 'rate' => 0.0];
        }

        $ratePercent = (float) $rate->rate / 100;
        $base = round($total / (1 + $ratePercent), 2);
        $tax = round($total - $base, 2);

        return ['base' => $base, 'tax' => $tax, 'total' => round($total, 2), 'label' => $rate->tax_label, 'rate' => (float) $rate->rate];
    }

    /**
     * Real charge: adds tax forward onto a base price (e.g. a one-time
     * purchase built fresh at checkout time, not tied to a fixed Stripe
     * price) — the returned total is what actually gets charged.
     *
     * @return array{base: float, tax: float, total: float, label: ?string, rate: float}
     */
    public function addToBase(Business $business, float $base): array
    {
        $rate = $this->rateFor($business);

        if (! $rate) {
            return ['base' => round($base, 2), 'tax' => 0.0, 'total' => round($base, 2), 'label' => null, 'rate' => 0.0];
        }

        $tax = round($base * (float) $rate->rate / 100, 2);

        return ['base' => round($base, 2), 'tax' => $tax, 'total' => round($base + $tax, 2), 'label' => $rate->tax_label, 'rate' => (float) $rate->rate];
    }

    private function isHomeCountry(?string $country): bool
    {
        return in_array(strtoupper(trim($country ?? '')), self::HOME_COUNTRY_CODES, true);
    }

    private function normalizeProvince(?string $raw): ?string
    {
        return ProvinceCodes::normalize($raw);
    }
}
