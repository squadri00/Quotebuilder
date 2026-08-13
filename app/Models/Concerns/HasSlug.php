<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Auto-generates a URL-friendly "slug" from the model's name when it's
 * created, e.g. "Printing Shop" -> "printing-shop". Uniqueness is checked
 * via slugQuery(), which models can override (see Product, which scopes
 * uniqueness to its own business rather than checking globally).
 *
 * Slugs are only generated once, on creation — renaming a business or
 * product later does not change its slug, since that would break any
 * links already shared with customers.
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = $model->makeUniqueSlug();
            }
        });
    }

    protected function makeUniqueSlug(): string
    {
        $base = Str::slug($this->name) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($this->slugQuery($slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Override this in the model to narrow uniqueness (e.g. per-business)
     * — call baseSlugQuery() from the override rather than "parent::",
     * since trait methods aren't reachable that way once overridden.
     */
    protected function slugQuery(string $slug)
    {
        return $this->baseSlugQuery($slug);
    }

    protected function baseSlugQuery(string $slug)
    {
        return static::withoutGlobalScopes()->where('slug', $slug);
    }
}
