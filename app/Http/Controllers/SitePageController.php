<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use Illuminate\View\View;

/**
 * Renders any admin-editable public page by slug — Privacy Policy and
 * Terms of Use are what the footer links to today, but this works for
 * any SitePage Super Admin adds later (see SuperAdmin\SitePageController)
 * without needing a new route or view.
 */
class SitePageController extends Controller
{
    public function show(string $slug): View
    {
        $page = SitePage::where('slug', $slug)->firstOrFail();

        return view('legal-page', compact('page'));
    }
}
