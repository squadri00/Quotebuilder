<?php

namespace App\Models\Concerns;

/**
 * Keeps is_published honest: the moment a published row is actually
 * edited, it flips back to false (a fresh Publish is what sets it true
 * again). This is a per-row indicator only — the thing that actually
 * gates what the public quote builder sees is Product's
 * published_snapshot, not these flags. See App\Models\Product.
 *
 * Bug fixed 2026-08-16: re-publishing an already-published product hit
 * this trait's own safety net by accident. Product::publish() does
 * `update(['is_published' => true, 'published_snapshot' => ..., ...])`
 * — but if is_published was ALREADY true, Eloquent's dirty-tracking
 * doesn't count "true" being set to "true" again as a real change, so
 * isDirty('is_published') came back false even though this genuinely was
 * a publish action. That made the condition below look identical to "an
 * unrelated field was edited without republishing," and it force-reset
 * is_published back to false — silently undoing the very save that was
 * supposed to just happen, while published_snapshot (which the update
 * call HAD changed) saved correctly. Net effect: a product could look
 * unpublished while actually holding valid published content, or (found
 * in the wild) get republished after being cloned into a business and
 * somehow surface the opposite split — published flag on with no
 * snapshot — depending on exactly when in that sequence this fired.
 * Publish flows now wrap their update() in withoutPublishTracking() to
 * suppress this trait entirely for that one call, rather than trying to
 * out-guess Eloquent's dirty-tracking about "same value set again."
 */
trait TracksPublishState
{
    protected static bool $publishTrackingSuppressed = false;

    protected static function bootTracksPublishState(): void
    {
        static::updating(function ($model) {
            if (static::$publishTrackingSuppressed) {
                return;
            }

            if ($model->is_published && $model->isDirty() && ! $model->isDirty('is_published')) {
                $model->is_published = false;
            }
        });
    }

    /**
     * Runs $callback with this safety net switched off — for the one
     * legitimate case where a save both is a real (re-)publish action
     * and needs is_published forced to true regardless of what it was a
     * moment ago. Restores the flag afterward even if $callback throws.
     */
    public static function withoutPublishTracking(callable $callback): mixed
    {
        static::$publishTrackingSuppressed = true;

        try {
            return $callback();
        } finally {
            static::$publishTrackingSuppressed = false;
        }
    }
}
