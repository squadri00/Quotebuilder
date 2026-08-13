<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\HasFormattedAddress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A business's own directory of people/companies they've quoted —
 * persists across quotes, unlike Quote::customer_name/customer_email
 * which are a frozen snapshot of what a customer was called *at the time
 * of that one quote*. Every quote source (public self-serve, staff
 * internal, Super Admin template demo) finds-or-creates one of these by
 * email so the directory builds itself from real usage; see
 * PublicQuoteController::store(), InternalQuoteController::persist(),
 * and SuperAdmin\InternalQuoteController::persist().
 */
class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use BelongsToBusiness, HasFactory, HasFormattedAddress;

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'notes',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
