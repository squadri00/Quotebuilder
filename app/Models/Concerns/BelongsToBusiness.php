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
            if (Auth::guard('web')->check()) {
                $builder->where(
                    $builder->getModel()->getTable().'.business_id',
                    Auth::guard('web')->user()->business_id
                );
            }
        });

        static::creating(function ($model) {
            if (! $model->business_id && Auth::guard('web')->check()) {
                $model->business_id = Auth::guard('web')->user()->business_id;
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
