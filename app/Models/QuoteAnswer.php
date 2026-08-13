<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deliberately does NOT use the BelongsToBusiness trait — it's always
 * reached through its parent Quote, which is already tenant-scoped.
 * business_id is still stored (copied from the quote) purely so this
 * table can be indexed and queried directly without a join, per
 * docs/conventions.md.
 */
class QuoteAnswer extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'quote_id',
        'question_id',
        'option_id',
        'answer_value',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $answer) {
            if (! $answer->business_id && $answer->quote) {
                $answer->business_id = $answer->quote->business_id;
            }
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
}
