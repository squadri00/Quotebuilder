<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateProspect extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id', 'company_name', 'company_name_norm', 'contact_name',
        'phone', 'phone_norm', 'email', 'email_norm', 'website', 'domain_norm',
        'city', 'state_province', 'country', 'industry', 'estimated_plan_id',
        'status', 'notes', 'claimed_at', 'last_activity_at', 'claim_expires_at',
        'converted_business_id', 'converted_at', 'released_at', 'released_by',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'claim_expires_at' => 'datetime',
            'converted_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'partner_id');
    }

    public function estimatedPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'estimated_plan_id');
    }

    public function convertedBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'converted_business_id');
    }

    public function isClaimActive(): bool
    {
        return in_array($this->status, ['open', 'working'], true)
            && $this->released_at === null
            && $this->converted_business_id === null
            && $this->claim_expires_at?->isFuture();
    }

    public function scopeActiveClaims($query)
    {
        return $query->whereIn('status', ['open', 'working'])
            ->whereNull('released_at')
            ->whereNull('converted_business_id')
            ->where('claim_expires_at', '>', now());
    }
}
