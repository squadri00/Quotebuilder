<?php

namespace App\Http\Controllers\Affiliate;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstimatorController extends PortalController
{
    public function index(Request $request): View
    {
        $partner = $this->partner();

        $rate = (float) $request->input('rate', $partner->commission_rate);
        $price = (float) $request->input('price', 0);
        $count = (int) $request->input('count', 5);
        $months = max(1, (int) $request->input('months', 18));
        $newPerMonth = (int) $request->input('new_per_month', 2);

        $perAccountMonthly = round($price * $rate / 100, 2);
        $perAccountTotal = round($perAccountMonthly * $months, 2);
        $cohortTotal = round($perAccountTotal * $count, 2);

        $year1 = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $year1 += $newPerMonth * min($m, $months) * $perAccountMonthly;
        }

        return view('affiliate.portal.estimator', [
            'partner' => $partner,
            'plans' => Plan::where('is_active', true)->where('billing_interval', 'monthly')->orderBy('price')->get(['name', 'price']),
            'inputs' => compact('rate', 'price', 'count', 'months', 'newPerMonth'),
            'results' => [
                'per_account_monthly' => $perAccountMonthly,
                'per_account_total' => $perAccountTotal,
                'cohort_total' => $cohortTotal,
                'year1' => round($year1, 2),
            ],
        ]);
    }
}
