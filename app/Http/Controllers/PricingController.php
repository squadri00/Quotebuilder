<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $tiers = Plan::groupedActiveTiers();

        return view('pricing', compact('tiers'));
    }

    /**
     * A header/footer-less version of the same pricing content, meant to
     * be dropped into an <iframe> on someone else's site (e.g. a Mobirise
     * HTML block) — see public/embed.js's sibling resize protocol via
     * partials/_embed-resize.
     */
    public function embed(): View
    {
        $tiers = Plan::groupedActiveTiers();

        // Sign Up needs to land on the Mobirise site's own register.php
        // wrapper (which embeds /embed/register in its own iframe), not
        // Laravel's bare /register — and target="_top" breaks out of the
        // pricing iframe so it replaces pricing.php in the same tab
        // rather than opening a second tab or getting stuck inside the
        // iframe. Swap this for the real production URL before going live.
        return view('pricing-embed', array_merge(compact('tiers'), [
            'registerUrl' => 'http://localhost/quotebuilder/register.php',
            'linkTarget' => 'target="_top"',
        ]));
    }
}
