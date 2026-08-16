<?php

namespace App\Models\Concerns;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Apply to any Eloquent model with a business_id column to make it
 * tenant-scoped: queries automatically filter to the logged-in user's
 * business, and new records automatically get business_id filled in.
 */
trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $builder) {
            $businessId = static::currentTenantBusinessId();

            if ($businessId !== null) {
                $builder->where($builder->getModel()->getTable().'.business_id', $businessId);
            }
        });

        static::creating(function ($model) {
            if (! $model->business_id) {
                $model->business_id = static::currentTenantBusinessId();
            }
        });
    }

    /**
     * Laravel allows more than one guard to be authenticated in the same
     * session at once — e.g. a business account logged into one browser
     * tab and Super Admin logged into another tab of the SAME browser,
     * sharing one session cookie. Without this check, that combination
     * silently scopes every Super Admin query down to whichever business
     * the 'web' guard happens to be logged into elsewhere in that same
     * session — it looks exactly like missing data (0 products, 0
     * questions) when nothing is actually missing. The 'web' guard is
     * only trusted for tenant scoping when this request isn't ALSO
     * authenticated as 'admin'.
     */
    protected static function currentTenantBusinessId(): ?int
    {
        if (Auth::guard('admin')->check()) {
            return null;
        }

        return Auth::guard('web')->check() ? Auth::guard('web')->user()->business_id : null;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
