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
     * sharing one session cookie. Checking Auth::guard('admin')->check()
     * alone doesn't tell you which guard THIS request is actually using —
     * it's true any time an admin session cookie exists anywhere, even on
     * a plain business-side request — which used to null out business_id
     * (and crash inserts like TaxRate::create()) the moment a Super Admin
     * tab was open in the same browser as a business tab. Checking the
     * current route's name instead of raw guard state tells us which side
     * this specific request belongs to: every Super Admin route is named
     * "superadmin.*" (see routes/superadmin.php), and Super Admin's own
     * controllers already set business_id explicitly wherever it's
     * needed, so scoping is safe to skip only there.
     */
    protected static function currentTenantBusinessId(): ?int
    {
        if (request()?->routeIs('superadmin.*')) {
            return null;
        }

        return Auth::guard('web')->check() ? Auth::guard('web')->user()->business_id : null;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
