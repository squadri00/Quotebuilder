<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageHero extends Model
{
    protected $fillable = ['page_key', 'eyebrow_text', 'heading', 'subheading'];

    /**
     * Fetch the hero row for a page, creating it from the given
     * defaults the first time it's accessed so public views never
     * have to guard against a missing row.
     */
    public static function forPage(string $key, array $defaults): self
    {
        return static::firstOrCreate(['page_key' => $key], $defaults);
    }
}
