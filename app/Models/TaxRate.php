<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'title',
        'description',
        'rate',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }
}
