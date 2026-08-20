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

    /**
     * Renders the heading with **text** wrapped in a green accent span,
     * matching the two-tone style used on the rest of the site's
     * section headers. The whole string is escaped first, so the
     * ** markers are the only thing that can introduce markup.
     */
    public function headingHtml(): string
    {
        return preg_replace('/\*\*(.+?)\*\*/s', '<span class="text-green-600 dark:text-green-400">$1</span>', e($this->heading));
    }
}
