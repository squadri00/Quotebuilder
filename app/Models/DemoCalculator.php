<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One card on the public Demo page (see DemoController::index()) — the
 * copy/icon shown, and which real template Business's Quote Hub it opens
 * inline via public/embed.js. Managed from Super Admin's Website
 * Management > Demo Calculators screen, so adding, editing, reordering,
 * or disabling one never needs a code change.
 */
class DemoCalculator extends Model
{
    /** @use HasFactory<\Database\Factories\DemoCalculatorFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
