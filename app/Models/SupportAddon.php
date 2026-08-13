<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton settings for the standalone Priority Support subscription —
 * one row, always id 1 (seeded by its migration). Use SupportAddon::get()
 * rather than querying directly, so callers never have to think about
 * "what if the row doesn't exist."
 */
class SupportAddon extends Model
{
    protected $table = 'support_addon';

    protected $fillable = [
        'name',
        'price',
        'stripe_price_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function get(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function isPurchasable(): bool
    {
        return $this->is_active && ! empty($this->stripe_price_id);
    }
}
