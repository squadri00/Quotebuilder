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
}
