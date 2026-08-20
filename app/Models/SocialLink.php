<?php

namespace App\Models;

use App\Support\SocialPlatforms;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $fillable = ['platform', 'label', 'url', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The custom label (only meaningful for platform "other") falls
     * back to the curated platform's own display name.
     */
    public function displayLabel(): string
    {
        return $this->label ?: SocialPlatforms::label($this->platform);
    }

    public function icon(): array
    {
        return SocialPlatforms::icon($this->platform);
    }
}
