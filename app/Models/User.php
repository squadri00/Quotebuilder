<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\TeamPermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_MEMBER = 'member';

    public const ROLES = [self::ROLE_OWNER, self::ROLE_MEMBER];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'business_id',
        'is_active',
        'theme',
        'role',
        'permissions',
    ];

    /**
     * A DB column default alone isn't enough here: Eloquent's create()
     * doesn't re-fetch the row afterward, so any column left out of the
     * insert (like is_active, when nothing sets it explicitly) stays null
     * on the in-memory model until it's refreshed — which is exactly the
     * object handed to Auth::login() right after registration. Setting
     * the default here means it's correct immediately, no refresh needed.
     *
     * role defaults to owner for the same reason — every path that
     * creates a User without explicitly passing a role (signup, the
     * paid-checkout webhook finalization) is creating the one account
     * that started the business, so it must be the owner. The one path
     * that creates a non-owner (TeamController::acceptInvitation) always
     * passes role explicitly, overriding this default.
     */
    protected $attributes = [
        'is_active' => true,
        'role' => self::ROLE_OWNER,
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Products this user (role=member) has explicitly been granted access
     * to. Meaningless for the Owner, who can access every product in
     * their business regardless of this — see canAccessProduct().
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    /**
     * The Owner can access every product in the business; a Member is
     * restricted to whichever products have been explicitly granted via
     * the product_user pivot.
     */
    public function canAccessProduct(Product $product): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->products()->where('products.id', $product->id)->exists();
    }

    /**
     * Only the Owner manages the team — inviting, changing permissions/
     * product access, deactivating, or removing anyone. Never grantable —
     * intentionally absent from TeamPermissions.
     */
    public function canManageTeam(): bool
    {
        return $this->isOwner();
    }

    /**
     * Billing (payment methods, subscription, invoices) is Owner-only,
     * always. Never grantable — intentionally absent from
     * TeamPermissions, unlike everything else in the app.
     */
    public function canAccessBilling(): bool
    {
        return $this->isOwner();
    }

    /**
     * Everything else in the app (Tax Rates, Business Settings,
     * Announcements, Support, and anything added later) is grantable
     * piecemeal to a Member via this — the Owner always has all of them.
     * See App\Support\TeamPermissions for the definitive list of keys.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return in_array($key, $this->permissions ?? [], true);
    }

    /**
     * Kept as a named helper (rather than a bare hasPermission() call at
     * every call site) since Tax Rates and Business Settings routes were
     * gated together before permissions became individually grantable —
     * this is now just a thin wrapper.
     */
    public function canManageBusinessSettings(): bool
    {
        return $this->hasPermission(TeamPermissions::BUSINESS_SETTINGS);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'business_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }
}
