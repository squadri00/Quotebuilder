<?php

namespace App\Models\Concerns;

/**
 * Keeps is_published honest: the moment a published row is actually
 * edited, it flips back to false (a fresh Publish is what sets it true
 * again). This is a per-row indicator only — the thing that actually
 * gates what the public quote builder sees is Product's
 * published_snapshot, not these flags. See App\Models\Product.
 */
trait TracksPublishState
{
    protected static function bootTracksPublishState(): void
    {
        static::updating(function ($model) {
            if ($model->is_published && $model->isDirty() && ! $model->isDirty('is_published')) {
                $model->is_published = false;
            }
        });
    }
}
