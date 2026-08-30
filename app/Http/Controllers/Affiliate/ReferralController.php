<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Services\Affiliate\AffiliateSettings;
use App\Services\Affiliate\AttributionService;
use Illuminate\Http\Request;

/**
 * Public referral-link entry point:  GET /r/{code}
 * Logs the click, drops the attribution cookie, forwards to the site.
 */
class ReferralController extends Controller
{
    public function track(Request $request, string $code, AttributionService $attribution)
    {
        $code = AttributionService::normalizeCode($code);

        $redirect = redirect('/');

        if ($code !== '' && AffiliateSettings::enabled()) {
            $attribution->logClick($code, $request);
            $redirect->withCookie($attribution->cookieForCode($code));
        }

        return $redirect;
    }
}
