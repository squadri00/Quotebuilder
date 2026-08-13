<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImplementationTier extends Model
{
    protected $fillable = [
        'name',
        'product_count',
        'price',
        'stripe_price_id',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'product_count' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ImplementationOrder::class);
    }

    public function isPurchasable(): bool
    {
        return $this->is_active && ! empty($this->stripe_price_id);
    }
}
