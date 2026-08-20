<?php

namespace App\Models;

use App\Models\Concerns\HasFormattedAddress;
use App\Models\Concerns\HasSlug;
use App\Services\PlatformTaxCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Billable;

class Business extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessFactory> */
    use Billable, HasFactory, HasFormattedAddress, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'industry_id',
        'onboarding_dismissed_at',
        'is_template',
        'is_active',
        'created_from_template_id',
        'plan_id',
        'support_access_granted',
        'address_line1',
        'address_line2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'timezone',
        'notification_email',
        'phone',
        'quotation_disclaimer',
        'public_quote_validity_days',
        'public_pdf_download_enabled',
        'public_email_enabled',
        'logo_path',
        'brand_color',
        'quote_number_format',
        'quote_number_prefix',
        'quote_number_next',
    ];

    /**
     * See App\Models\User for why this needs to be set here, not just
     * left to the migration's column default.
     */
    protected $attributes = [
        'is_active' => true,
        'is_template' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
            'is_active' => 'boolean',
            'support_access_granted' => 'boolean',
            'public_pdf_download_enabled' => 'boolean',
            'public_email_enabled' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The one account this business was signed up under — every business
     * has exactly one (see User::ROLE_OWNER), regardless of how many
     * team members get invited afterward. Handy anywhere a single
     * "the business's email" is needed, e.g. the Super Admin businesses
     * list, since a business itself has no login/email of its own.
     */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', User::ROLE_OWNER);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Every template this business was actually built from at signup —
     * could be zero, one, or several. createdFromTemplate() (via
     * created_from_template_id) is kept separately as "the first one
     * picked," for the places that only ever expected a single template;
     * this is the complete, accurate picture.
     */
    public function templatesUsed(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_template', 'business_id', 'template_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function trainingArtifacts(): HasMany
    {
        return $this->hasMany(BusinessTrainingArtifact::class);
    }

    /**
     * The subset of this business's products the given user may see/work
     * in — every product for the Owner, only explicitly granted ones for
     * a Member. Used everywhere a product list needs filtering (Products
     * index, Rules index/picker, Quotes inbox), so the restriction stays
     * consistent instead of being reimplemented per controller.
     */
    public function productsAccessibleTo(User $user)
    {
        if ($user->isOwner()) {
            return $this->products();
        }

        return $this->products()->whereHas('users', fn ($query) => $query->where('users.id', $user->id));
    }

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function implementationOrders(): HasMany
    {
        return $this->hasMany(ImplementationOrder::class);
    }

    /**
     * Named shopTaxRates (not taxRates) — Cashier's own Billable trait
     * already defines a taxRates() method for its unrelated built-in
     * "Stripe tax rate IDs to auto-apply to subscriptions" feature, and
     * silently overriding it with an incompatible return type breaks
     * SubscriptionBuilder::checkout() at runtime.
     */
    public function shopTaxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    public function createdFromTemplate(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'created_from_template_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Whether this business's current plan includes the given feature.
     * A business with no plan assigned has no features. Not yet used to
     * gate anything in the app — just the check itself, for future use.
     */
    public function hasFeature(string $key): bool
    {
        return $this->plan?->features()->where('key', $key)->exists() ?? false;
    }

    /**
     * Priority Support is a standalone add-on subscription (Cashier's
     * 'support' named subscription), not a Plan feature — a business can
     * have it regardless of which plan they're on, or even with no plan
     * at all. support_access_granted is the platform's manual override —
     * same "regardless of what Stripe says" pattern already used for
     * plan_id — for comped access, VIP accounts, or anyone paying outside
     * Stripe. A plan can also bundle it in for free via the
     * 'priority_support' feature (e.g. the Professional tier).
     */
    public function hasSupportAccess(): bool
    {
        return $this->support_access_granted || $this->subscribed('support') || $this->hasFeature('priority_support');
    }

    /**
     * Cashier's own extension point (see Billable::taxRates(), which
     * defaults to []) — whatever this returns gets attached as
     * default_tax_rates on every subscription Cashier creates for this
     * business (newSubscription()->checkout()/create()), and Stripe then
     * re-applies it on every future renewal invoice on its own. Unlike the
     * old approach of merging tax into a one-time price, this survives
     * plan swaps automatically too, since Subscription::swap() never
     * touches default_tax_rates.
     *
     * Empty for anywhere outside Canada, or if no matching rate has been
     * set up in Stripe yet (see PlatformTaxRate::stripe_tax_rate_id) —
     * either way, Stripe simply adds nothing.
     */
    public function taxRates(): array
    {
        $rate = (new PlatformTaxCalculator)->rateFor($this);

        return $rate?->stripe_tax_rate_id ? [$rate->stripe_tax_rate_id] : [];
    }

    /**
     * Max products this business's plan allows, or null for unlimited.
     * A business with no plan assigned gets 0 — same "no plan = nothing"
     * rule as hasFeature().
     */
    public function productLimit(): ?int
    {
        return $this->plan ? $this->plan->max_products : 0;
    }

    /**
     * Only ACTIVE products count against the plan limit — deliberately,
     * so a business at their cap can make room for something new by
     * deactivating an old product instead of having to delete it. An
     * inactive product is already fully hidden from customers (same as
     * deleted, as far as anyone outside the business can tell), so it
     * costs nothing to leave it sitting there switched off — all its
     * data, and any quote history tied to it, just stays intact.
     */
    public function hasReachedProductLimit(): bool
    {
        $limit = $this->productLimit();

        return $limit !== null && $this->products()->where('is_active', true)->count() >= $limit;
    }

    /**
     * Max quotes this business's plan allows per calendar month, or null
     * for unlimited. No plan assigned = 0, same rule as productLimit().
     */
    public function monthlyQuoteLimit(): ?int
    {
        return $this->plan ? $this->plan->max_quotes_per_month : 0;
    }

    /**
     * The start of this business's CURRENT billing cycle — anchored to
     * the day-of-month their subscription actually renews on (or, for a
     * business with no real Stripe subscription — free plan, or a plan
     * Super Admin assigned by hand, see BusinessController::assignPlan()
     * — the day-of-month they originally signed up on), not the 1st of
     * the calendar month. A business that signed up on the 15th should
     * see their quote count reset on the 15th, same as their real
     * invoice date, not get a free reset on the 1st and another one
     * mid-cycle. Never calls Stripe for this — the anchor day-of-month is
     * stable and known locally, so there's nothing to look up.
     *
     * Clamped to whichever month's actual last day, for an anchor like
     * the 31st in a shorter month (Carbon's day() would otherwise roll
     * over into the next month entirely, e.g. "Feb 31st" becoming March
     * 3rd). Re-clamped separately against the PREVIOUS month too when
     * rolling back to it, rather than reusing the current month's clamp
     * — a 31st-anchor business checked in February clamps to the 28th
     * for February itself, but rolling back from there must land on
     * January's real 31st, not carry February's 28 backwards into a
     * month that actually had 31 days of its own.
     */
    public function currentBillingPeriodStart(): Carbon
    {
        $anchorDay = ($this->subscription('default')?->created_at ?? $this->created_at)->day;

        $periodStart = $this->clampedAnchorDate(now(), $anchorDay);

        if ($periodStart->greaterThan(now())) {
            $periodStart = $this->clampedAnchorDate(now()->copy()->subMonthNoOverflow(), $anchorDay);
        }

        return $periodStart;
    }

    private function clampedAnchorDate(Carbon $referenceMonth, int $anchorDay): Carbon
    {
        return $referenceMonth->copy()->startOfDay()->day(min($anchorDay, $referenceMonth->daysInMonth));
    }

    /**
     * When the count below next resets — purely for showing the business
     * when their usage clears, not used in the limit check itself.
     */
    public function nextQuoteResetDate(): Carbon
    {
        return $this->currentBillingPeriodStart()->addMonthNoOverflow();
    }

    public function quotesThisMonthCount(): int
    {
        return $this->quotes()
            ->where('created_at', '>=', $this->currentBillingPeriodStart())
            ->count();
    }

    public function hasReachedMonthlyQuoteLimit(): bool
    {
        $limit = $this->monthlyQuoteLimit();

        return $limit !== null && $this->quotesThisMonthCount() >= $limit;
    }

    /**
     * Claims and formats this business's next quote reference number
     * (e.g. "42" or "INV-0042", per quote_number_format/quote_number_prefix
     * — see Business Settings), atomically so two quotes created at the
     * same moment can never collide. Every quote gets exactly one of
     * these, assigned once at creation and never reassigned — see
     * Quote::reference_number's docblock in the migration for why a quote
     * predating this feature just falls back to showing its plain #id.
     */
    public function nextQuoteReferenceNumber(): string
    {
        return DB::transaction(function () {
            $business = self::withoutGlobalScopes()->whereKey($this->id)->lockForUpdate()->first();

            $number = $business->quote_number_next;
            $business->increment('quote_number_next');

            return $business->quote_number_format === 'alphanumeric'
                ? trim((string) $business->quote_number_prefix).str_pad((string) $number, 4, '0', STR_PAD_LEFT)
                : (string) $number;
        });
    }

    /**
     * Max team seats this business's plan allows, or null for unlimited.
     * No plan assigned = 0, same rule as productLimit(). The Owner always
     * counts as one of these seats (see activeUserCount()) — a plan of 1
     * means "just the owner, no invitable team members."
     */
    public function userLimit(): ?int
    {
        return $this->plan ? $this->plan->max_users : 0;
    }

    /**
     * Active users plus outstanding (unaccepted) invitations — an invite
     * counts as an occupied seat the moment it's sent, not just once
     * accepted, otherwise a business could send unlimited invitations and
     * only get capped when people actually click "accept."
     */
    public function activeUserCount(): int
    {
        // TeamInvitation is queried with its global tenant scope bypassed
        // and business_id filtered explicitly instead — this method needs
        // to give the right count for *this* business regardless of which
        // (if any) business the currently logged-in user belongs to.
        return $this->users()->where('is_active', true)->count()
            + TeamInvitation::withoutGlobalScopes()->where('business_id', $this->id)->whereNull('accepted_at')->count();
    }

    public function hasReachedUserLimit(): bool
    {
        $limit = $this->userLimit();

        return $limit !== null && $this->activeUserCount() >= $limit;
    }

    /**
     * The PHP timezone identifier to display this business's dates in
     * (quote submission times, etc.) — everything is still stored in UTC
     * exactly as before; this only controls what a viewer sees. Falls
     * back to the platform's own timezone for a business that hasn't set
     * one, same "unset means default" pattern as brand_color.
     */
    public function timezone(): string
    {
        return $this->timezone ?: config('app.timezone');
    }

    /**
     * Seeded into every brand-new business's quotation_disclaimer at
     * signup (see RegisteredUserController::store() and
     * Stripe\WebhookController's pending-registration finalizer) — same
     * text regardless of whether a template was picked, since
     * TemplateCloner only clones catalog data (products/rules/questions),
     * never business-profile fields like this one. Still fully editable
     * afterward from Business Settings, same as any hand-typed value.
     * Deliberately NOT applied to Super Admin's own template businesses
     * (TemplateController::store()) — those are Super Admin's own testing
     * catalogs, not real customer-facing accounts.
     */
    public static function defaultQuotationDisclaimer(): string
    {
        return <<<'TEXT'
        This quotation is valid for 30 days from the date of issue, unless otherwise stated. Prices are based on the specifications provided at the time of quoting and are subject to change if requirements, quantities, or materials differ from what was described.

        Prices do not include applicable taxes, shipping, or delivery charges unless explicitly listed above. Payment terms and any deposit requirements will be confirmed separately before work begins.

        This quote does not constitute a binding contract or authorization to proceed. Work will only commence upon written confirmation and, where applicable, receipt of any required deposit.

        Errors and omissions excepted (E&OE). We reserve the right to correct any pricing or calculation errors found after this quote was issued.
        TEXT;
    }
}
