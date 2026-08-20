<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide reference data — which countries a business can register
 * from, and the currency to show alongside their price for it (short_code
 * matching PlatformTaxRate::country_code so tax lookups and this table
 * agree on the same 2-letter codes). Deliberately separate from
 * PlatformTaxRate: adding a country here doesn't imply the platform
 * charges tax there — only Canada does, by design (see
 * PlatformTaxCalculator) — this table is registration + currency-display
 * data only.
 */
class Country extends Model
{
    protected $fillable = [
        'name',
        'short_code',
        'currency_code',
        'currency_symbol',
        'currency_position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
