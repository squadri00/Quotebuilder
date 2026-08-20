<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A cached currency-conversion rate — always the latest known rate for a
 * given pair (one row per from/to combination, upserted, not a history
 * log). Purely a display-layer cache for CurrencyConverter; never used
 * for anything that actually gets charged.
 */
class FxRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'fetched_at' => 'datetime',
        ];
    }
}
