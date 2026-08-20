<?php

namespace App\Http\Controllers;

use App\Models\MarketingFeature;
use App\Models\PageHero;
use Illuminate\View\View;

class FeaturesController extends Controller
{
    public function show(): View
    {
        $hero = PageHero::forPage('features', [
            'eyebrow_text' => 'Everything you need to quote faster',
            'heading' => 'From no-code pricing rules to instant PDF quotes, ' . config('app.name', 'Quotaire') . ' gives you every tool to turn browsers into buyers.',
            'subheading' => 'Build custom calculators, automate your pricing logic, and deliver instant quotes your customers can trust — all without writing a single line of code.',
        ]);

        $features = MarketingFeature::where('page_key', 'features')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('features', compact('hero', 'features'));
    }
}
