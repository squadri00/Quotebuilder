<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per paid subscription-renewal invoice that actually carried
 * tax (see Business::taxRates()) — the local record-keeping the platform
 * owner needs for their own HST/GST remittance, since Stripe's own
 * invoice history has no simple aggregate view for this. Written once,
 * from Stripe\WebhookController::handleInvoicePaymentSucceeded(), never
 * edited afterward — it's a ledger, not a live-editable total.
 */
class TaxCollection extends Model
{
    protected $fillable = [
        'business_id',
        'subscription_type',
        'stripe_invoice_id',
        'amount',
        'currency',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'collected_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
