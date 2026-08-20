<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MarketingFeature;
use App\Models\PageHero;
use App\Support\MarketingFeatureIcons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeaturesPageController extends Controller
{
    private const PAGE_KEY = 'features';

    public function index(): View
    {
        $hero = PageHero::forPage(self::PAGE_KEY, [
            'eyebrow_text' => 'Everything you need to quote faster',
            'heading' => 'From no-code pricing rules to instant PDF quotes, ' . config('app.name', 'Quotaire') . ' gives you every tool to turn browsers into buyers.',
            'subheading' => 'Build custom calculators, automate your pricing logic, and deliver instant quotes your customers can trust — all without writing a single line of code.',
        ]);

        $cards = MarketingFeature::where('page_key', self::PAGE_KEY)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('superadmin.features-page.index', compact('hero', 'cards'));
    }

    public function updateHero(Request $request): RedirectResponse
    {
        $hero = PageHero::where('page_key', self::PAGE_KEY)->firstOrFail();

        $hero->update($request->validate([
            'eyebrow_text' => ['nullable', 'string', 'max:80'],
            'heading' => ['required', 'string', 'max:400'],
            'subheading' => ['required', 'string', 'max:600'],
        ]));

        return redirect()->route('superadmin.features-page.index')->with('status', 'Hero content updated.');
    }

    public function createCard(): View
    {
        return view('superadmin.features-page.create');
    }

    public function storeCard(Request $request): RedirectResponse
    {
        $card = MarketingFeature::create(array_merge(
            $this->validatedCard($request),
            ['page_key' => self::PAGE_KEY]
        ));

        return redirect()->route('superadmin.features-page.index')->with('status', "\"{$card->title}\" added.");
    }

    public function editCard(MarketingFeature $card): View
    {
        return view('superadmin.features-page.edit', compact('card'));
    }

    public function updateCard(Request $request, MarketingFeature $card): RedirectResponse
    {
        $card->update($this->validatedCard($request));

        return redirect()->route('superadmin.features-page.index')->with('status', "\"{$card->title}\" updated.");
    }

    public function destroyCard(MarketingFeature $card): RedirectResponse
    {
        $title = $card->title;
        $card->delete();

        return redirect()->route('superadmin.features-page.index')->with('status', "\"{$title}\" deleted.");
    }

    public function toggleCardActive(MarketingFeature $card): RedirectResponse
    {
        $card->update(['is_active' => ! $card->is_active]);

        return redirect()->route('superadmin.features-page.index')
            ->with('status', "\"{$card->title}\" is now " . ($card->is_active ? 'active' : 'disabled') . '.');
    }

    private function validatedCard(Request $request): array
    {
        $validated = $request->validate([
            'icon' => ['required', 'string', Rule::in(MarketingFeatureIcons::paths())],
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:600'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
