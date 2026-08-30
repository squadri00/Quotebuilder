<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_id', 'partner_id', 'business_id', 'source_tax_collection_id',
        'business_name_snapshot', 'period_year', 'period_month', 'base_amount',
        'currency', 'rate', 'commission_amount', 'kind', 'status', 'payout_id',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'rate' => 'decimal:3',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(AffiliateReferral::class, 'referral_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'partner_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(AffiliatePayout::class, 'payout_id');
    }
}
