<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'billing_interval',
        'stripe_price_id',
        'max_products',
        'max_quotes_per_month',
        'max_users',
        'marketing_bullets',
        'is_highlighted',
        'is_active',
    ];

    /**
     * See App\Models\User for why this needs to be set here, not just
     * left to the migration's column default: Eloquent's create() doesn't
     * re-fetch the row, so a freshly-created Plan's in-memory is_active
     * would otherwise stay null until refreshed.
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_products' => 'integer',
            'max_quotes_per_month' => 'integer',
            'max_users' => 'integer',
            'is_highlighted' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features');
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * marketing_bullets is stored as one bullet per line (a plain textarea
     * in the Super Admin form) — this splits it into a clean list for the
     * public pricing page.
     */
    public function marketingBulletsList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $this->marketing_bullets ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Active plans grouped into one entry per tier — monthly/yearly
     * variants of the same tier share a name (e.g. two rows both called
     * "Growth") and get paired up as ['monthly' => Plan|null, 'yearly' =>
     * Plan|null], sorted cheapest first. Shared by the public pricing page
     * and the business Billing page so both toggle the same way.
     */
    public static function groupedActiveTiers(): Collection
    {
        return static::where('is_active', true)
            ->orderBy('price')
            ->get()
            ->groupBy('name')
            ->map(fn ($group) => [
                'monthly' => $group->firstWhere('billing_interval', 'monthly'),
                'yearly' => $group->firstWhere('billing_interval', 'yearly'),
            ])
            ->filter(fn ($tier) => $tier['monthly'] || $tier['yearly'])
            ->sortBy(fn ($tier) => ($tier['monthly'] ?? $tier['yearly'])->price)
            ->values();
    }
}
