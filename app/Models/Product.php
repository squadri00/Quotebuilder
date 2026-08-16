<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksPublishState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, BelongsToBusiness, HasSlug, TracksPublishState;

    protected $fillable = [
        'business_id',
        'source_template_product_id',
        'name',
        'slug',
        'description',
        'base_price',
        'is_active',
        'show_in_quote_hub',
        'quote_hub_sort_order',
        'is_published',
        'published_snapshot',
        'published_at',
        'attrib1',
        'attrib2',
        'attrib3',
        'num1',
        'num2',
        'num3',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
            'show_in_quote_hub' => 'boolean',
            'is_published' => 'boolean',
            'published_snapshot' => 'array',
            'published_at' => 'datetime',
            'num1' => 'decimal:2',
            'num2' => 'decimal:2',
            'num3' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function sourceTemplateProduct(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_template_product_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Team members (role=member) explicitly granted access to this
     * product. See User::canAccessProduct().
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Slugs only need to be unique within this product's own business —
     * two businesses can each have a product slugged "flyers".
     */
    protected function slugQuery(string $slug)
    {
        return $this->baseSlugQuery($slug)->where('business_id', $this->business_id);
    }

    /**
     * The exact shape stored in published_snapshot and served to the
     * public quote builder. Built fresh from current live data — used
     * both to save a new snapshot (on Publish) and to check whether the
     * live data has drifted from what's already published.
     */
    public function buildPublishableSnapshot(): array
    {
        $this->loadMissing(['questions.options', 'rules', 'images']);

        return [
            'product' => [
                'name' => $this->name,
                'description' => $this->description,
                'base_price' => (float) $this->base_price,
            ],
            'images' => $this->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $image->url,
            ])->all(),
            'questions' => $this->questions->sortBy('sort_order')->values()->map(fn (Question $question) => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'type' => $question->type,
                'sort_order' => $question->sort_order,
                'display_conditions' => $question->display_conditions,
                'options' => $question->options->sortBy('id')->values()->map(fn (Option $option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'price_modifier' => (float) $option->price_modifier,
                ])->all(),
            ])->all(),
            'rules' => $this->rules->sortBy('id')->values()->map(fn (Rule $rule) => [
                'id' => $rule->id,
                'name' => $rule->name,
                'condition_logic' => $rule->condition_logic,
                'action_logic' => $rule->action_logic,
            ])->all(),
        ];
    }

    /**
     * True if the product has never been published, or if anything in
     * its current draft (product fields, questions, options, rules —
     * including additions/deletions) differs from the last published
     * snapshot.
     */
    public function hasUnpublishedChanges(): bool
    {
        if (! $this->published_snapshot) {
            return true;
        }

        // Compared as JSON rather than with !== because the stored value
        // already made one round trip through the database's JSON column:
        // e.g. a price_modifier of 0.0 comes back decoded as the integer 0,
        // not the float 0.0, which would make an otherwise-identical fresh
        // snapshot look different under strict array comparison.
        return json_encode($this->buildPublishableSnapshot()) !== json_encode($this->published_snapshot);
    }
}
