<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Quote;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $businesses = Business::where('is_template', false);

        $totalBusinesses = (clone $businesses)->count();
        $activeBusinesses = (clone $businesses)->where('is_active', true)->count();
        $inactiveBusinesses = $totalBusinesses - $activeBusinesses;

        // Excludes quotes generated against template businesses (used for
        // internal testing/demoing a template's catalog, never real
        // customer activity) so this total reconciles with the
        // per-business breakdown below it.
        $totalQuotes = Quote::whereHas('business', fn ($q) => $q->where('is_template', false))->count();

        $topBusinessesByQuotes = Business::where('is_template', false)
            ->withCount('quotes')
            ->orderByDesc('quotes_count')
            ->limit(8)
            ->get();

        $signups = $this->dailySignupCounts();

        return view('superadmin.dashboard', [
            'totalBusinesses' => $totalBusinesses,
            'activeBusinesses' => $activeBusinesses,
            'inactiveBusinesses' => $inactiveBusinesses,
            'totalQuotes' => $totalQuotes,
            'topBusinessesByQuotes' => $topBusinessesByQuotes,
            'signups' => $signups,
        ]);
    }

    /**
     * Day-by-day signup counts for the last 14 days, zero-filled so the
     * chart always has a full, evenly-spaced axis even on quiet days.
     */
    private function dailySignupCounts(): array
    {
        $start = Carbon::today()->subDays(13);

        $counted = Business::where('is_template', false)
            ->where('created_at', '>=', $start->copy()->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $days = [];

        for ($i = 0; $i < 14; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $days[$date] = (int) ($counted[$date] ?? 0);
        }

        return $days;
    }
}
