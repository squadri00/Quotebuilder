<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id', 'partner_code', 'ip_hash', 'user_agent',
        'landing_path', 'referrer', 'converted_business_id',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'partner_id');
    }
}
