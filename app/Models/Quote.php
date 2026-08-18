<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory, BelongsToBusiness;

    public const STATUSES = ['New', 'Contacted', 'Won', 'Lost'];

    public const SOURCE_PUBLIC = 'public';

    public const SOURCE_INTERNAL = 'internal';

    public const SOURCES = [self::SOURCE_PUBLIC, self::SOURCE_INTERNAL];

    /**
     * The only expiration windows staff can pick on the Review screen —
     * see InternalQuoteController::calculate() and its Super Admin mirror.
     */
    public const EXPIRATION_DAY_OPTIONS = [14, 30, 60, 90];

    protected $fillable = [
        'business_id',
        'reference_number',
        'revises_quote_id',
        'product_id',
        'customer_name',
        'customer_email',
        'customer_id',
        'final_price',
        'calculated_price',
        'status',
        'source',
        'created_by',
        'internal_notes',
        'emailed_at',
        'expires_at',
        'prepared_by_name',
        'prepared_by_email',
        'uuid',
        'attrib1',
        'attrib2',
        'attrib3',
        'num1',
        'num2',
        'num3',
        'meta',
    ];

    protected $attributes = [
        'status' => 'New',
        'source' => self::SOURCE_PUBLIC,
    ];

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            $quote->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'final_price' => 'decimal:2',
            'calculated_price' => 'decimal:2',
            'num1' => 'decimal:2',
            'num2' => 'decimal:2',
            'num3' => 'decimal:2',
            'meta' => 'array',
            'emailed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The directory Customer this quote is linked to, if any — nullable
     * because quotes predating the Customers feature (and any whose
     * Customer was later deleted) have none. customer_name/customer_email
     * on the quote itself stay the source of truth for what to display;
     * this is only for "show me this customer's other quotes."
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quoteAnswers(): HasMany
    {
        return $this->hasMany(QuoteAnswer::class);
    }

    /**
     * The quote this one revises, if any — see InternalQuoteController::
     * persist()'s docblock for when a revision gets created instead of
     * updating a quote in place.
     */
    public function revisesQuote(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revises_quote_id');
    }

    /**
     * Any quotes that revise THIS one — usually zero or one, but nothing
     * stops a revision from itself being revised again later, so this
     * stays a HasMany rather than a single nullable relation.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'revises_quote_id');
    }

    /**
     * The number shown to staff and printed on the PDF — the business's
     * own configured reference_number when this quote has one, or a
     * plain #id for a quote that predates that feature. Every view should
     * go through this rather than reading reference_number/id directly,
     * so "how do we show a quote's number" only has to be decided once.
     */
    public function displayReference(): string
    {
        return $this->reference_number ?? (string) $this->id;
    }

    /**
     * Which staff member created this — only ever set for source=internal.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isInternal(): bool
    {
        return $this->source === self::SOURCE_INTERNAL;
    }

    /**
     * True only when a staff member actually typed a different number into
     * the price-override field — not just because calculated_price happens
     * to be set (it's always set for internal quotes, even when unchanged).
     */
    public function hasPriceOverride(): bool
    {
        return $this->isInternal()
            && $this->calculated_price !== null
            && bccomp((string) $this->calculated_price, (string) $this->final_price, 2) !== 0;
    }

    /**
     * Only ever set on quotes created through Super Admin's demo-quote
     * tool (see SuperAdmin\InternalQuoteController) — a fictional
     * business identity typed in for that one quote, never the real
     * business's own data. null for every ordinary public/internal quote,
     * which is exactly why every view checks this before falling back to
     * $quote->business->name.
     */
    public function demoBusinessOverride(): ?array
    {
        return $this->meta['demo_business'] ?? null;
    }

    /**
     * The customer's phone/address as they stood at the moment this quote
     * was created — frozen into meta at creation time (same "freeze it"
     * pattern as applied_rules/tax_lines/selections), never read live off
     * the Customer record, since a customer's saved details can change
     * after the fact and a quote should keep showing what was true when
     * it was quoted. Empty array (not null) for quotes that predate this
     * feature, so callers can safely check phone/address_lines directly.
     */
    public function customerContact(): array
    {
        return $this->meta['customer_contact'] ?? [];
    }

    /**
     * Internal-only, staff-set expiration (14/30/60/90 days, picked on the
     * Review screen) — never set on public customer-submitted quotes.
     * false for a quote with no expiration chosen, same as false once it's
     * genuinely past that date.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
