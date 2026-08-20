<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingFeature extends Model
{
    protected $fillable = ['page_key', 'icon', 'title', 'body', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
