<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AffiliatePartner extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guard = 'affiliate';

    protected $fillable = [
        'partner_code', 'name', 'email', 'password', 'phone', 'company_name',
        'tax_id', 'address', 'city', 'state_province', 'postal_code', 'country',
        'commission_rate', 'payout_method', 'payout_details', 'status', 'notes',
        'agreement_accepted_at', 'approved_at', 'approved_by', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'commission_rate' => 'decimal:3',
            'agreement_accepted_at' => 'datetime',
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class, 'partner_id');
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(AffiliateProspect::class, 'partner_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'partner_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class, 'partner_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class, 'partner_id');
    }

    public function referralUrl(): string
    {
        return url('/r/' . $this->partner_code);
    }
}
