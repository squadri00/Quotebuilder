<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single admin-editable public page — Privacy Policy and Terms of Use
 * ship as the first two (see PrivacyPolicySeeder/routes/web.php's
 * /legal/{slug} route), but any slug works, so Super Admin's Website >
 * Site Pages screen can add more later without a code change.
 *
 * "Last updated" is deliberately just this row's own updated_at — it
 * reflects a real edit the moment one happens, same as every other
 * admin-editable field in this app, rather than a separate date an
 * admin has to remember to update by hand.
 */
class SitePage extends Model
{
    /** @use HasFactory<\Database\Factories\SitePageFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'content',
    ];
}
