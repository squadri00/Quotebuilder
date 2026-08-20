<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\View\View;

/**
 * The public marketing "Demo" page — six industry cards, each linking to
 * a real, live public quote picker for one of our own template businesses
 * (see TemplateCloner/Business::is_template), so "Try the demo" opens an
 * actual working calculator instead of a placeholder.
 */
class DemoController extends Controller
{
    private const INDUSTRIES = [
        [
            'slug' => 'printing-shop',
            'name' => 'Printing & Signage',
            'description' => 'Quote business cards, banners, flyers and bulk print runs with pricing that adjusts by size, quantity, paper stock, and finish.',
            'icon' => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h14z',
        ],
        [
            'slug' => 'hvac-contractor',
            'name' => 'HVAC',
            'description' => 'Estimate installs, repairs, and tune-ups based on unit type, home size, and system complexity — no truck roll needed.',
            'icon' => 'M12 8v8m-4-5v5m8-9v9M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z',
        ],
        [
            'slug' => 'cabinet-millwork-shop',
            'name' => 'Cabinets & Millwork',
            'description' => 'Quote custom cabinetry by material, finish, dimensions, and hardware — with pricing that updates live as customers choose.',
            'icon' => 'M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm0 6h16M10 6v12',
        ],
        [
            'slug' => 'custom-manufacturing-fabrication-shop',
            'name' => 'Custom Manufacturing & Fabrication',
            'description' => 'Price custom fabrication jobs by material, dimensions, and process — with conditional questions for spec-driven work.',
            'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
        ],
        [
            'slug' => 'kitchen-bathroom-remodeler',
            'name' => 'Kitchen & Bathroom Remodeling',
            'description' => 'Estimate remodels by room size, fixtures, and finish level, so homeowners get a real ballpark before booking a consultation.',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        ],
        [
            'slug' => 'custom-furniture-maker',
            'name' => 'Custom Furniture',
            'description' => 'Price custom furniture builds by dimensions, wood species, and finish, with automatic quantity discounts built into the rules.',
            'icon' => 'M4 6h16M4 12h16M4 18h7',
        ],
    ];

    public function index(): View
    {
        $industries = collect(self::INDUSTRIES)->map(function (array $industry) {
            $business = Business::where('slug', $industry['slug'])->where('is_active', true)->first();

            return [...$industry, 'business' => $business];
        })->filter(fn (array $industry) => $industry['business'] !== null)->values();

        return view('demo', ['industries' => $industries]);
    }
}
