<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformTaxRate extends Model
{
    protected $fillable = [
        'country_code',
        'province',
        'tax_label',
        'rate',
        'is_active',
        'stripe_tax_rate_id',
    ];

    protected $attributes = [
        'country_code' => 'CA',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }
}
