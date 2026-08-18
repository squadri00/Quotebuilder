<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Plan extends Model
{
    public const DISCOUNT_DISPLAYS = ['fixed', 'percentage'];

    protected $fillable = [
        'name',
        'price',
        'compare_at_price',
        'show_discount',
        'discount_display',
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
            'compare_at_price' => 'decimal:2',
            'show_discount' => 'boolean',
            'max_products' => 'integer',
            'max_quotes_per_month' => 'integer',
            'max_users' => 'integer',
            'is_highlighted' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * True only when there's an actual discount to show — the toggle is
     * on, a "was" price is set, and it's genuinely higher than the real
     * price. Never trusts show_discount alone, since a stale compare_at_price
     * left over from an old promotion (e.g. equal to or below the current
     * price) shouldn't render a nonsensical "$0 off" badge.
     */
    public function hasDiscount(): bool
    {
        return $this->show_discount
            && $this->compare_at_price !== null
            && (float) $this->compare_at_price > (float) $this->price;
    }

    /**
     * The dollar amount to show as "you save" — always derived live from
     * compare_at_price minus price, never stored separately, so it can
     * never drift out of sync with the two real numbers driving it.
     */
    public function discountAmount(): float
    {
        return $this->hasDiscount() ? round((float) $this->compare_at_price - (float) $this->price, 2) : 0.0;
    }

    public function discountPercent(): int
    {
        return $this->hasDiscount() ? (int) round($this->discountAmount() / (float) $this->compare_at_price * 100) : 0;
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
