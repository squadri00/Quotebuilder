<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\FinancialSummaryService;
use Illuminate\View\View;

/**
 * The platform owner's one place to see what's actually happening
 * financially — MRR, revenue and tax collected, broken down by the three
 * things businesses pay for (plan subscriptions, Priority Support,
 * Implementation Service). All figures come from FinancialSummaryService,
 * the same source the home Dashboard's overview card reads from, so the
 * two never disagree.
 */
class FinancialController extends Controller
{
    public function __construct(private FinancialSummaryService $financials) {}

    public function index(): View
    {
        $monthStart = now()->startOfMonth();

        $thisMonth = $this->financials->breakdown($monthStart);
        $allTime = $this->financials->breakdown();

        $breakdown = [];
        foreach ($allTime as $source => $amount) {
            $breakdown[$source] = [
                'this_month' => $thisMonth[$source],
                'all_time' => $amount,
            ];
        }

        return view('superadmin.financial.index', [
            'mrr' => $this->financials->mrr(),
            'pastDue' => $this->financials->pastDue(),
            'activeSubscriptionCounts' => $this->financials->activeSubscriptionCounts(),
            'revenueThisMonth' => $this->financials->revenue($monthStart),
            'revenueAllTime' => $this->financials->revenue(),
            'taxThisMonth' => $this->financials->tax($monthStart),
            'taxAllTime' => $this->financials->tax(),
            'breakdown' => $breakdown,
            'recentActivity' => $this->financials->recentActivity(),
        ]);
    }
}
