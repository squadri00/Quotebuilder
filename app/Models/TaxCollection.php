<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per successfully paid subscription-renewal invoice (default
 * plan or Priority Support) — started as purely a tax ledger for the
 * platform owner's own HST/GST remittance, since Stripe's own invoice
 * history has no simple aggregate view for that; now doubles as the
 * general revenue ledger behind Super Admin's Financial Activity report,
 * since the same one row per invoice already has everything needed
 * (base_amount + amount [tax] = total_amount). Written once, from
 * Stripe\WebhookController::handleInvoicePaymentSucceeded(), for every
 * successful invoice regardless of whether tax applied (amount is simply
 * 0 for a non-Canadian business) — never edited afterward, it's a
 * ledger, not a live-editable total.
 */
class TaxCollection extends Model
{
    protected $fillable = [
        'business_id',
        'subscription_type',
        'stripe_invoice_id',
        'amount',
        'base_amount',
        'total_amount',
        'currency',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'collected_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
