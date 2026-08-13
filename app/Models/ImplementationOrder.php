<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImplementationOrder extends Model
{
    public const STATUSES = ['awaiting_payment', 'paid', 'in_progress', 'completed'];

    protected $fillable = [
        'business_id',
        'implementation_tier_id',
        'tier_name',
        'product_count',
        'price',
        'tax_label',
        'tax_rate',
        'tax_amount',
        'status',
        'stripe_checkout_session_id',
        'paid_at',
    ];

    protected $attributes = [
        'status' => 'awaiting_payment',
        'tax_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'product_count' => 'integer',
            'price' => 'decimal:2',
            'tax_rate' => 'decimal:3',
            'tax_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function totalCharged(): float
    {
        return round((float) $this->price + (float) $this->tax_amount, 2);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ImplementationTier::class, 'implementation_tier_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'awaiting_payment' => 'Awaiting Payment',
            'paid' => 'Paid — Awaiting Fulfillment',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            default => ucfirst($this->status),
        };
    }
}
