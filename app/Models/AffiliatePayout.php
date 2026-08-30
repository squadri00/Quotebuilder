<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliatePayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_number', 'partner_id', 'period_year', 'period_month',
        'period_start', 'period_end', 'due_date', 'currency', 'commission_count',
        'base_total', 'rate_snapshot', 'amount', 'status', 'finalized_at',
        'submitted_at', 'paid_at', 'payment_reference', 'download_count',
        'last_downloaded_at', 'partner_name_snapshot', 'partner_email_snapshot',
        'partner_address_snapshot', 'partner_citystate_snapshot',
        'partner_country_snapshot', 'partner_tax_id_snapshot',
        'company_name_snapshot', 'company_address_snapshot',
        'company_email_snapshot', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'base_total' => 'decimal:2',
            'rate_snapshot' => 'decimal:3',
            'amount' => 'decimal:2',
            'finalized_at' => 'datetime',
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'partner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'payout_id');
    }

    public function periodLabel(): string
    {
        return \Carbon\Carbon::create($this->period_year, $this->period_month, 1)->format('F Y');
    }
}
