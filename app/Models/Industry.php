<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A platform-wide, Super-Admin-curated classification for businesses and
 * templates (e.g. "Printing", "Landscaping") — used to recommend a
 * relevant template at registration. Deliberately global and small in
 * shape (just a name): unlike App\Models\Category (a business's own,
 * private product organization), an Industry is shared vocabulary
 * everyone picks from, not something each business defines for itself.
 */
class Industry extends Model
{
    protected $fillable = [
        'name',
    ];

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * Just the template businesses tagged with this industry (not real
     * customer accounts) — what the Industries page links out to, since
     * "go see this industry's products" means the template's catalog.
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Business::class)->where('is_template', true);
    }
}
