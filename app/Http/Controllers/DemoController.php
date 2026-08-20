<?php

namespace App\Http\Controllers;

use App\Models\DemoCalculator;
use Illuminate\View\View;

/**
 * The public marketing "Demo" page — industry cards, each linking to a
 * real, live public quote picker for one of our own template businesses
 * (see TemplateCloner/Business::is_template). Which cards exist, their
 * copy/icon, and whether they're shown is entirely managed from Super
 * Admin's Website Management > Demo Calculators screen — see
 * DemoCalculator and SuperAdmin\DemoCalculatorController.
 */
class DemoController extends Controller
{
    public function index(): View
    {
        $demoCalculators = DemoCalculator::with('business')
            ->where('is_active', true)
            ->whereHas('business', fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $industries = $demoCalculators->map(fn (DemoCalculator $demoCalculator) => [
            'name' => $demoCalculator->name,
            'description' => $demoCalculator->description,
            'icon' => $demoCalculator->icon,
            'business' => $demoCalculator->business,
        ]);

        return view('demo', ['industries' => $industries]);
    }
}
