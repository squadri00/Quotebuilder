<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A registration that hasn't been paid for yet. Holds everything needed
 * to create the real Business + User once Stripe confirms payment
 * (see Stripe\WebhookController::finalizePendingRegistration) — nothing
 * is created up front, so an abandoned checkout never leaves behind a
 * usable, unpaid account.
 */
class PendingRegistration extends Model
{
    protected $fillable = [
        'token',
        'name',
        'company_name',
        'email',
        'password',
        'template_business_id',
        'template_business_ids',
        'product_ids',
        'industry_id',
        'country',
        'state_province',
        'plan_id',
        'business_id',
    ];

    protected function casts(): array
    {
        return [
            'template_business_ids' => 'array',
            'product_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PendingRegistration $pending) {
            $pending->token ??= (string) Str::uuid();
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function isFinalized(): bool
    {
        return $this->business_id !== null;
    }
}
