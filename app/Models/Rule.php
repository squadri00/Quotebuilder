<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\TracksPublishState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rule extends Model
{
    /** @use HasFactory<\Database\Factories\RuleFactory> */
    use HasFactory, BelongsToBusiness, TracksPublishState;

    protected $fillable = [
        'business_id',
        'product_id',
        'name',
        'condition_logic',
        'action_logic',
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
            'condition_logic' => 'array',
            'action_logic' => 'array',
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
}
