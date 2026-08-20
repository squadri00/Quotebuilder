<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Services\CurrencyConverter;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $tiers = Plan::groupedActiveTiers();

        $masterCurrency = strtoupper(PlatformSetting::get()->default_currency ?? 'CAD');

        $fxRates = (new CurrencyConverter)->ratesFrom($masterCurrency);

        // Symbols come from the Countries table (Super Admin > Countries)
        // rather than being hardcoded here, so adding a country there is
        // enough to get its symbol showing correctly in this conversion
        // line too.
        $currencySymbols = Country::whereIn('currency_code', array_keys($fxRates))
            ->pluck('currency_symbol', 'currency_code');

        // The actual plan prices are always stored (and charged via
        // Stripe) in whichever currency is set here — the main price
        // display must show its real symbol, not an assumed "$", or a
        // GBP-priced plan showing "$29" would look like it's charging a
        // different, cheaper currency than what actually happens at
        // checkout.
        $masterCountry = Country::where('currency_code', $masterCurrency)->first();
        $masterSymbol = $masterCountry->currency_symbol ?? '$';
        $masterSymbolAfter = $masterCountry?->currency_position === 'after';

        return view('pricing', compact('tiers', 'fxRates', 'currencySymbols', 'masterSymbol', 'masterSymbolAfter'));
    }
}
