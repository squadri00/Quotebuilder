<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\SetupGuideService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SetupGuideService $setupGuide): View
    {
        $business = Auth::user()->business;
        $business->loadMissing('industry');

        $products = $business->products()->with(['questions.options', 'rules'])->get();

        $progress = $setupGuide->progress($business);

        $quoteLimit = $business->monthlyQuoteLimit();
        // Same method the actual limit check (Business::hasReachedMonthlyQuoteLimit)
        // uses — was previously a separate calendar-month count computed
        // right here, which quietly disagreed with what the real limit
        // check counted once that switched to the business's own billing-
        // cycle anchor instead of the calendar month.
        $quotesThisMonth = $business->quotesThisMonthCount();

        // Same billing-cycle anchor as quotesThisMonth above, split by
        // source so the dashboard can show public vs. internal separately.
        $billingPeriodStart = $business->currentBillingPeriodStart();
        $publicQuotesAllTime = $business->quotes()->where('source', Quote::SOURCE_PUBLIC)->count();
        $internalQuotesAllTime = $business->quotes()->where('source', Quote::SOURCE_INTERNAL)->count();
        $publicQuotesThisMonth = $business->quotes()->where('source', Quote::SOURCE_PUBLIC)->where('created_at', '>=', $billingPeriodStart)->count();
        $internalQuotesThisMonth = $business->quotes()->where('source', Quote::SOURCE_INTERNAL)->where('created_at', '>=', $billingPeriodStart)->count();

        return view('dashboard', [
            'business' => $business,
            'progress' => $progress,
            'showOnboarding' => ! $progress['allDone'] && is_null($business->onboarding_dismissed_at),
            'productCount' => $products->count(),
            'activeProductCount' => $products->where('is_active', true)->count(),
            'draftProductCount' => $products->filter->hasUnpublishedChanges()->count(),
            'quoteCount' => $business->quotes()->count(),
            'quotesThisMonth' => $quotesThisMonth,
            'quoteLimit' => $quoteLimit,
            'quoteResetDate' => $quoteLimit !== null ? $business->nextQuoteResetDate() : null,
            'publicQuotesAllTime' => $publicQuotesAllTime,
            'internalQuotesAllTime' => $internalQuotesAllTime,
            'publicQuotesThisMonth' => $publicQuotesThisMonth,
            'internalQuotesThisMonth' => $internalQuotesThisMonth,
            'recentQuotes' => $business->hasFeature('quote_inbox')
                ? $business->quotes()->with('product')->latest()->limit(5)->get()
                : null,
        ]);
    }
}
