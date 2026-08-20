<?php

namespace Database\Seeders;

use App\Models\MarketingFeature;
use App\Models\PageHero;
use Illuminate\Database\Seeder;

/**
 * Seeds the Features page's original hardcoded hero copy and feature
 * cards as real, editable rows the first time this runs. Idempotent —
 * safe to re-run without duplicating or overwriting existing content.
 */
class FeaturesPageSeeder extends Seeder
{
    public function run(): void
    {
        PageHero::firstOrCreate(
            ['page_key' => 'features'],
            [
                'eyebrow_text' => 'Everything you need to quote faster',
                'heading' => 'From no-code pricing rules to instant PDF quotes, Quotaire gives you every tool to turn browsers into buyers.',
                'subheading' => 'Build custom calculators, automate your pricing logic, and deliver instant quotes your customers can trust — all without writing a single line of code. See how each feature works together to save you time and win more business.',
            ]
        );

        if (MarketingFeature::where('page_key', 'features')->exists()) {
            return;
        }

        $cards = [
            ['icon' => 'M9 3v2m6-2v2M4 7h16M5 7h14v13a1 1 0 01-1 1H6a1 1 0 01-1-1V7z', 'title' => 'No-code quote builder', 'body' => 'Build fully custom pricing calculators without writing a single line of code. Add products, questions, and options through simple point-and-click forms — no developer needed, ever.'],
            ['icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'title' => 'Smart pricing rules engine', 'body' => 'Set up fixed fees, percentage surcharges, price overrides, and quantity-based pricing with simple dropdown logic. Your pricing can be as simple or as detailed as your business needs.'],
            ['icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'title' => 'Question paths (conditional logic)', 'body' => 'Show customers only the questions that apply to them. Ask about delivery details only if they choose delivery, or show finish options only for certain products — keeping the quote experience fast and relevant.'],
            ['icon' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9 10h6m-6 4h4', 'title' => 'Embed anywhere', 'body' => 'Paste one line of code and your quote calculator works on any website — WordPress, Wix, Squarespace, or custom-built. Fully responsive on desktop, tablet, and mobile.'],
            ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'title' => 'Instant branded PDF quotes', 'body' => 'Every completed quote generates a professional, itemized PDF automatically — with your logo, pricing breakdown, and terms — ready to email or download in seconds.'],
            ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'title' => 'Quotes inbox & status tracking', 'body' => 'Every quote request lands in one organized inbox. Track status from new to sent to accepted, so nothing falls through the cracks.'],
        ];

        foreach ($cards as $index => $card) {
            MarketingFeature::create([
                'page_key' => 'features',
                'icon' => $card['icon'],
                'title' => $card['title'],
                'body' => $card['body'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
