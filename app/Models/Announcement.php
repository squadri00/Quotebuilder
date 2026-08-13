<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Super Admin platform announcements shown to business staff — same
 * concept as Meccora's broadcasts, adapted to QuoteBuilder's own data
 * model (no trial/active business status here, so targeting uses
 * plan_id presence instead).
 */
class Announcement extends Model
{
    public const SEVERITIES = ['info', 'warning', 'critical'];

    public const AUDIENCES = ['all', 'with_plan', 'no_plan', 'specific'];

    protected $fillable = [
        'title',
        'message',
        'severity',
        'target_audience',
        'target_business_id',
        'send_email',
        'email_sent_at',
        'expires_at',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'severity' => 'info',
        'target_audience' => 'all',
        'send_email' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'send_email' => 'boolean',
            'is_active' => 'boolean',
            'email_sent_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function targetBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'target_business_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Announcements whose audience setting includes the given business —
     * regardless of active/expired/dismissed state. The base of both the
     * "unread right now" and "full history" queries below.
     */
    public function scopeMatchingBusiness(Builder $query, Business $business): Builder
    {
        return $query->where(function (Builder $q) use ($business) {
            $q->where('target_audience', 'all')
                ->orWhere('target_audience', $business->plan_id !== null ? 'with_plan' : 'no_plan')
                ->orWhere(function (Builder $q2) use ($business) {
                    $q2->where('target_audience', 'specific')
                        ->where('target_business_id', $business->id);
                });
        });
    }

    /**
     * Active, unexpired announcements matching this business that this
     * user hasn't dismissed — most severe first. This is what the
     * dashboard banner shows, and calling it marks each as read (that's
     * what drives the nav badge count back down), mirroring Meccora.
     *
     * @return Collection<int, self>
     */
    public static function unreadForBusiness(Business $business, int $userId): Collection
    {
        $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];

        $announcements = static::query()
            ->matchingBusiness($business)
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with(['reads' => fn ($q) => $q->where('user_id', $userId)])
            ->get()
            ->filter(fn (Announcement $a) => $a->reads->first()?->dismissed_at === null)
            ->sortBy(fn (Announcement $a) => $severityOrder[$a->severity] ?? 3)
            ->values();

        $announcements->each(fn (Announcement $a) => $a->markReadIfUnread($userId));

        return $announcements;
    }

    /**
     * Every announcement ever matching this business's audience —
     * dismissed, expired, or withdrawn included — so staff can always
     * re-read something they already closed. Powers the Announcements
     * page. Also marks as read, same as unreadForBusiness(), so the nav
     * badge stays consistent regardless of which page someone reads on.
     *
     * @return Collection<int, self>
     */
    public static function allForBusiness(Business $business, int $userId): Collection
    {
        $announcements = static::query()
            ->matchingBusiness($business)
            ->with(['reads' => fn ($q) => $q->where('user_id', $userId)])
            ->latest()
            ->get();

        $announcements->each(fn (Announcement $a) => $a->markReadIfUnread($userId));

        return $announcements;
    }

    /**
     * Count of matching, active, unexpired announcements this user
     * hasn't read yet — the nav badge. Side-effect-free (doesn't mark
     * anything as read), unlike the two methods above.
     */
    public static function unreadCountForBusiness(Business $business, int $userId): int
    {
        return static::query()
            ->matchingBusiness($business)
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId)->whereNotNull('read_at'))
            ->count();
    }

    public function markReadIfUnread(int $userId): void
    {
        $existing = $this->reads->first();

        if ($existing?->read_at !== null) {
            return;
        }

        AnnouncementRead::updateOrCreate(
            ['announcement_id' => $this->id, 'user_id' => $userId],
            ['read_at' => now()]
        );
    }

    /**
     * Dismiss implies read (a critical announcement's single
     * "Acknowledge" action covers both in one step).
     */
    public function dismiss(int $userId): void
    {
        AnnouncementRead::updateOrCreate(
            ['announcement_id' => $this->id, 'user_id' => $userId],
            ['dismissed_at' => now(), 'read_at' => now()]
        );
    }
}
