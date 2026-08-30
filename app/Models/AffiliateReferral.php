<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id', 'business_id', 'prospect_id', 'source', 'commission_rate',
        'status', 'approved_at', 'approved_by', 'ended_at', 'ended_reason',
        'first_commission_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:3',
            'approved_at' => 'datetime',
            'ended_at' => 'datetime',
            'first_commission_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'partner_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(AffiliateProspect::class, 'prospect_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'referral_id');
    }

    public function effectiveRate(): float
    {
        return (float) ($this->commission_rate ?? $this->partner->commission_rate);
    }
}
