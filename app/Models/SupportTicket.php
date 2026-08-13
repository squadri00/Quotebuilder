<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory, BelongsToBusiness;

    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];

    protected $fillable = [
        'business_id',
        'user_id',
        'tracking_number',
        'subject',
        'message',
        'status',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * Tracking number is derived from the row's own auto-increment id, so
     * it can only be assigned after the insert — unlike Quote's uuid,
     * which can be generated up front. Same technique as Meccora's
     * support system.
     */
    protected static function booted(): void
    {
        static::created(function (SupportTicket $ticket) {
            if (! $ticket->tracking_number) {
                $ticket->updateQuietly(['tracking_number' => 'SUP-' . str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->orderBy('created_at');
    }

    /**
     * A reply is, by definition, someone having started working the
     * ticket — mirrors how real ticketing systems behave without
     * requiring the admin to separately remember to flip the status too.
     */
    public function markInProgressIfOpen(): void
    {
        if ($this->status === 'open') {
            $this->update(['status' => 'in_progress']);
        }
    }
}
