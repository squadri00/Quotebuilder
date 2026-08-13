<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $business = Auth::user()->business;

        $products = $business->products()->with(['questions.options', 'rules'])->get();

        return view('dashboard', [
            'business' => $business,
            'showOnboarding' => is_null($business->industry_id) && is_null($business->onboarding_dismissed_at),
            'productCount' => $products->count(),
            'activeProductCount' => $products->where('is_active', true)->count(),
            'draftProductCount' => $products->filter->hasUnpublishedChanges()->count(),
            'quoteCount' => $business->quotes()->count(),
            'quotesThisMonth' => $business->quotes()->where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            'recentQuotes' => $business->hasFeature('quote_inbox')
                ? $business->quotes()->with('product')->latest()->limit(5)->get()
                : null,
        ]);
    }
}
