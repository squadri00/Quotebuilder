<?php

namespace App\Http\Controllers;

use App\Models\PageHero;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function show(): View
    {
        $hero = PageHero::forPage('home', [
            'eyebrow_text' => null,
            'heading' => 'Build Your Own Quote Calculator',
            'subheading' => 'Create custom quote calculators for your business. Set your products, options, pricing and rules, then use them internally or give your customers a simple way to get a quote online.',
        ]);

        return view('welcome', compact('hero'));
    }
}
