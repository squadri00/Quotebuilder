<?php

namespace App\Services;

use App\Models\Business;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Auth;

/**
 * Drives the post-signup "Setup Guide" (see OnboardingController and
 * resources/views/onboarding/create.blade.php). Each step's "done" state is
 * read live off the business's real data — not a manually-ticked checkbox —
 * so the guide always reflects whether a step is actually finished, e.g. a
 * tax rate deleted after being added flips that step back to not-done.
 */
class SetupGuideService
{
    /**
     * @return array<int, array{key: string, label: string, description: string, done: bool, url: ?string, cta: ?string, optional: bool}>
     */
    public function steps(Business $business): array
    {
        $steps = [
            [
                'key' => 'industry',
                'label' => 'Pick your industry and starting products',
                'description' => 'Tell us what kind of business this is so we can suggest ready-made products to start from — or skip it and build everything from scratch.',
                'done' => filled($business->industry_id) || $business->products()->exists(),
                'url' => null,
                'cta' => null,
                'optional' => false,
            ],
            [
                'key' => 'profile',
                'label' => 'Add your business address and phone number',
                'description' => 'This is what shows up on every quote and PDF you send to a customer.',
                'done' => filled($business->address_line1) && filled($business->phone),
                'url' => route('profile.edit'),
                'cta' => 'Go to Business Profile',
                'optional' => false,
            ],
            [
                'key' => 'branding',
                'label' => 'Add your logo and brand color (optional)',
                'description' => 'Recommended, not required — it\'s what makes your quotes and PDFs look like they came from you instead of a generic template.',
                'done' => filled($business->logo_path),
                'url' => route('profile.edit'),
                'cta' => 'Go to Business Profile',
                'optional' => true,
            ],
            [
                'key' => 'tax',
                'label' => 'Add a tax rate',
                'description' => 'If you charge tax, add it here. This is easy to miss — skip it and every quote will silently show $0 tax with no warning.',
                'done' => $business->shopTaxRates()->where('is_active', true)->exists(),
                'url' => route('tax-rates.index'),
                'cta' => 'Go to Tax Rates',
                'optional' => false,
            ],
            [
                'key' => 'product',
                'label' => 'Create and publish a product',
                'description' => 'Build at least one product with a price, then click Publish on it. A product is invisible to staff and customers until it\'s published — even after you save it.',
                // published_snapshot, not is_published — the snapshot is
                // what actually gates the public quote page (see
                // PublicQuoteController), not the is_published flag. That
                // flag is just an advisory "you edited this since last
                // publish" reminder (see TracksPublishState) and flips
                // off on ANY save to the product — including one that
                // doesn't touch its published content at all, e.g.
                // toggling the unrelated "Active" checkbox — which
                // otherwise showed this step as "not done" for a product
                // that was, in every way that actually matters, still
                // fully published and working for customers.
                'done' => $business->products()->whereNotNull('published_snapshot')->exists(),
                'url' => route('products.index'),
                'cta' => 'Go to Products',
                'optional' => false,
            ],
        ];

        if (Auth::user()?->canManageTeam()) {
            $steps[] = [
                'key' => 'team',
                'label' => 'Invite your team (optional)',
                'description' => 'If anyone else needs access, invite them here and choose exactly what they can see and do.',
                'done' => $business->users()->count() > 1
                    || TeamInvitation::withoutGlobalScopes()->where('business_id', $business->id)->exists(),
                'url' => route('team.index'),
                'cta' => 'Go to Team',
                'optional' => true,
            ];
        }

        return $steps;
    }

    /**
     * @return array{completed: int, total: int, percent: int, allDone: bool}
     */
    public function progress(Business $business): array
    {
        $required = array_filter($this->steps($business), fn (array $step) => ! $step['optional']);
        $done = array_filter($required, fn (array $step) => $step['done']);

        $total = count($required);
        $completed = count($done);

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($completed / $total * 100) : 100,
            'allDone' => $completed === $total,
        ];
    }
}
