<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PageHero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomePageController extends Controller
{
    private const PAGE_KEY = 'home';

    public function index(): View
    {
        $hero = PageHero::forPage(self::PAGE_KEY, [
            'eyebrow_text' => null,
            'heading' => 'Build Your Own Quote Calculator',
            'subheading' => 'Create custom quote calculators for your business. Set your products, options, pricing and rules, then use them internally or give your customers a simple way to get a quote online.',
        ]);

        return view('superadmin.home-page.index', compact('hero'));
    }

    public function updateHero(Request $request): RedirectResponse
    {
        $hero = PageHero::where('page_key', self::PAGE_KEY)->firstOrFail();

        $hero->update($request->validate([
            'heading' => ['required', 'string', 'max:200'],
            'subheading' => ['required', 'string', 'max:600'],
        ]));

        return redirect()->route('superadmin.home-page.index')->with('status', 'Hero content updated.');
    }
}
