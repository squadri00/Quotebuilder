<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\TracksPublishState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory, BelongsToBusiness, TracksPublishState;

    protected $fillable = [
        'business_id',
        'product_id',
        'question_text',
        'description',
        'type',
        'sort_order',
        'display_conditions',
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
            'sort_order' => 'integer',
            'display_conditions' => 'array',
            'is_published' => 'boolean',
            'num1' => 'decimal:2',
            'num2' => 'decimal:2',
            'num3' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function quoteAnswers(): HasMany
    {
        return $this->hasMany(QuoteAnswer::class);
    }
}
