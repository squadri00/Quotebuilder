<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImplementationOrder extends Model
{
    public const STATUSES = ['awaiting_payment', 'paid', 'in_progress', 'completed', 'abandoned'];

    /**
     * Once an order reaches any of these, it's genuinely settled —
     * money has already changed hands (or the order is being fulfilled),
     * so nothing should ever overwrite it again. 'abandoned' is
     * deliberately NOT in this list: it's a best-effort label, not proof
     * payment can no longer happen, so a real payment must still be able
     * to land on top of it (see markPaidFromCheckoutSession()).
     */
    private const SETTLED_STATUSES = ['paid', 'in_progress', 'completed'];

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
            'abandoned' => 'Abandoned',
            default => ucfirst($this->status),
        };
    }

    /**
     * The one place an order is ever marked paid — used by both the real
     * webhook handler and the expiry command's missed-webhook
     * reconciliation, so both paths guarantee the exact same guard
     * (never overwrite an order that's already settled) and the exact
     * same fields get set either way. Deliberately treats 'abandoned' as
     * still payable: that label only means "looked unpaid last time we
     * checked," never "Stripe guarantees this can't be paid" — a real,
     * late payment must still be able to land on top of it rather than
     * being silently dropped.
     */
    public function markPaidFromCheckoutSession(string $sessionId): void
    {
        if (in_array($this->status, self::SETTLED_STATUSES, true)) {
            return;
        }

        $this->update([
            'status' => 'paid',
            'stripe_checkout_session_id' => $sessionId,
            'paid_at' => now(),
        ]);
    }

    /**
     * Only ever called once Stripe itself confirms the checkout session
     * has expired — at that point the customer can no longer pay through
     * that link, so it's safe to stop showing this in the active queue.
     */
    public function markAbandoned(): void
    {
        if ($this->status !== 'awaiting_payment') {
            return;
        }

        $this->update(['status' => 'abandoned']);
    }
}
