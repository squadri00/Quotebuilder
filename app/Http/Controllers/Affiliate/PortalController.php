<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePartner;
use Illuminate\Support\Facades\Auth;

abstract class PortalController extends Controller
{
    protected function partner(): AffiliatePartner
    {
        return Auth::guard('affiliate')->user();
    }
}
