<?php

namespace App\Services;

use App\Models\FxRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Display-only currency conversion for showing a price alongside its
 * approximate value in a few other currencies (e.g. "$29 CAD ≈ €18 EUR")
 * — never used for anything a customer is actually charged, which always
 * happens in whatever currency Stripe is configured for. Rates are
 * fetched live from a free, no-key-required API and cached in the
 * fx_rates table for a day, so a normal page view never waits on a
 * third-party call, and a temporary outage falls back to the last known
 * rate rather than hiding the conversion entirely.
 */
class CurrencyConverter
{
    private const CACHE_HOURS = 24;

    private const DISPLAY_CURRENCIES = ['CAD', 'USD', 'EUR', 'GBP', 'AUD'];

    /**
     * Rates from $masterCurrency to each of the standard display
     * currencies, excluding whichever one already equals the master
     * (nothing useful to convert to itself). Returns an empty array if
     * no rate could be found at all (first-ever run with the live API
     * unreachable) — callers should simply skip the display in that case
     * rather than show a broken or zeroed conversion.
     *
     * @return array<string, float>
     */
    public function ratesFrom(string $masterCurrency): array
    {
        $masterCurrency = strtoupper($masterCurrency);
        $targets = array_values(array_diff(self::DISPLAY_CURRENCIES, [$masterCurrency]));

        if (empty($targets)) {
            return [];
        }

        $rates = [];
        foreach ($targets as $target) {
            $rate = $this->rateFor($masterCurrency, $target);

            if ($rate !== null) {
                $rates[$target] = $rate;
            }
        }

        return $rates;
    }

    private function rateFor(string $from, string $to): ?float
    {
        $cached = FxRate::where('from_currency', $from)->where('to_currency', $to)->first();

        if ($cached && $cached->fetched_at->gt(now()->subHours(self::CACHE_HOURS))) {
            return (float) $cached->rate;
        }

        $fresh = $this->fetchLive($from, $to);

        if ($fresh !== null) {
            FxRate::updateOrCreate(
                ['from_currency' => $from, 'to_currency' => $to],
                ['rate' => $fresh, 'fetched_at' => now()]
            );

            return $fresh;
        }

        // Live fetch failed — a stale cached rate is still far more useful
        // to a visitor than no conversion at all.
        return $cached ? (float) $cached->rate : null;
    }

    private function fetchLive(string $from, string $to): ?float
    {
        try {
            $response = Http::timeout(5)->get('https://api.frankfurter.app/latest', [
                'from' => $from,
                'to' => $to,
            ]);

            $rate = $response->json('rates.'.$to);

            return is_numeric($rate) ? (float) $rate : null;
        } catch (\Throwable $e) {
            Log::warning('Live FX rate fetch failed.', ['from' => $from, 'to' => $to, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
