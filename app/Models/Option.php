<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\TracksPublishState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends Model
{
    /** @use HasFactory<\Database\Factories\OptionFactory> */
    use HasFactory, BelongsToBusiness, TracksPublishState;

    protected $fillable = [
        'business_id',
        'question_id',
        'label',
        'description',
        'price_modifier',
        'is_published',
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
            'price_modifier' => 'decimal:2',
            'is_published' => 'boolean',
            'num1' => 'decimal:2',
            'num2' => 'decimal:2',
            'num3' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function quoteAnswers(): HasMany
    {
        return $this->hasMany(QuoteAnswer::class);
    }
}
