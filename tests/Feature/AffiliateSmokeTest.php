<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AffiliatePartner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_affiliate_pages_render(): void
    {
        $admin = Admin::create([
            'name' => 'Smoke Admin', 'email' => 'admin-smoke@e.com', 'password' => 'password12',
        ]);
        $partner = AffiliatePartner::create([
            'partner_code' => 'SMOKE1', 'name' => 'Smoke', 'email' => 's@e.com',
            'password' => 'password12', 'commission_rate' => 20, 'status' => 'active',
        ]);

        $urls = [
            '/superadmin/affiliate',
            '/superadmin/affiliate/partners/create',
            "/superadmin/affiliate/partners/{$partner->id}",
            "/superadmin/affiliate/partners/{$partner->id}?tab=prospects",
            "/superadmin/affiliate/partners/{$partner->id}?tab=commissions",
            "/superadmin/affiliate/partners/{$partner->id}?tab=payouts",
            "/superadmin/affiliate/partners/{$partner->id}/edit",
            '/superadmin/affiliate/prospects',
            '/superadmin/affiliate/commissions',
            '/superadmin/affiliate/commissions?tab=ledger',
            '/superadmin/affiliate/payouts',
            '/superadmin/affiliate/settings',
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin, 'admin')->get($url)->assertOk();
        }
    }

    public function test_partner_portal_pages_render(): void
    {
        $partner = AffiliatePartner::create([
            'partner_code' => 'SMOKE2', 'name' => 'Smoke Two', 'email' => 's2@e.com',
            'password' => 'password12', 'commission_rate' => 25, 'status' => 'active',
        ]);

        foreach ([
            '/affiliate', '/affiliate/referrals', '/affiliate/prospects',
            '/affiliate/commissions', '/affiliate/statements', '/affiliate/estimator',
            '/affiliate/profile',
        ] as $url) {
            $this->actingAs($partner, 'affiliate')->get($url)->assertOk();
        }
    }

    public function test_commission_lifecycle_end_to_end(): void
    {
        \App\Models\PlatformSetting::get()->update(['affiliate_payout_currency' => 'CAD', 'affiliate_min_payout' => 5]);

        $partner = AffiliatePartner::create([
            'partner_code' => 'FLOW1', 'name' => 'Flow', 'email' => 'flow@e.com',
            'password' => 'password12', 'commission_rate' => 25, 'status' => 'active',
        ]);
        $business = \App\Models\Business::create(['name' => 'Flow Biz']);

        // Manual attach, not yet approved.
        $res = app(\App\Services\Affiliate\AttributionService::class)
            ->attachManual($business->id, $partner->id, null, false, null);
        $this->assertTrue($res['ok']);

        // Two paid platform invoices in the same month.
        foreach ([1, 2] as $i) {
            $tc = \App\Models\TaxCollection::create([
                'business_id' => $business->id, 'stripe_invoice_id' => "flow_$i",
                'amount' => 0, 'base_amount' => 40, 'total_amount' => 40,
                'currency' => 'CAD', 'collected_at' => now()->subMonth()->startOfMonth(),
            ]);
            app(\App\Services\Affiliate\CommissionService::class)->accrueForTaxCollection($tc);
        }

        // Accrued but pending (referral unapproved).
        $this->assertSame(2, \App\Models\AffiliateCommission::where('status', 'pending')->count());

        // Approve the referral -> commissions become approved.
        $referral = \App\Models\AffiliateReferral::first();
        app(\App\Services\Affiliate\CommissionService::class)->approveReferral($referral);
        $this->assertSame(2, \App\Models\AffiliateCommission::where('status', 'approved')->count());

        // Finalize a payout.
        $y = (int) now()->subMonth()->format('Y');
        $m = (int) now()->subMonth()->format('n');
        $out = app(\App\Services\Affiliate\PayoutService::class)->finalize($partner->id, $y, $m);
        $this->assertTrue($out['ok'], $out['error'] ?? '');
        $this->assertEquals(20.0, (float) $out['payout']->amount); // 2 * 40 * 25%
        $this->assertSame(2, $out['payout']->items()->where('status', 'on_payout')->count());

        // Mark paid -> commissions paid.
        app(\App\Services\Affiliate\PayoutService::class)->markPaid($out['payout'], 'ref-123');
        $this->assertSame('paid', $out['payout']->fresh()->status);
        $this->assertSame(2, \App\Models\AffiliateCommission::where('status', 'paid')->count());
    }

    public function test_prospect_claim_blocks_overlap(): void
    {
        $p1 = AffiliatePartner::create(['partner_code' => 'OV1', 'name' => 'A', 'email' => 'a@e.com',
            'password' => 'password12', 'commission_rate' => 20, 'status' => 'active']);
        $p2 = AffiliatePartner::create(['partner_code' => 'OV2', 'name' => 'B', 'email' => 'b@e.com',
            'password' => 'password12', 'commission_rate' => 20, 'status' => 'active']);

        $svc = app(\App\Services\Affiliate\ProspectService::class);
        $this->assertTrue($svc->claim($p1->id, ['company_name' => 'Acme Auto & Body', 'phone' => '403 555 1234'])['ok']);

        $blocked = $svc->claim($p2->id, ['company_name' => 'ACME  Auto and Body', 'phone' => '(403) 555-1234']);
        $this->assertFalse($blocked['ok']);
        $this->assertNotNull($blocked['conflict']);
    }

    public function test_referral_link_sets_cookie(): void
    {
        AffiliatePartner::create([
            'partner_code' => 'LINK1', 'name' => 'L', 'email' => 'l@e.com',
            'password' => 'password12', 'commission_rate' => 20, 'status' => 'active',
        ]);

        $this->get('/r/LINK1')
            ->assertRedirect('/')
            ->assertCookie('aff_ref');

        $this->assertDatabaseHas('affiliate_clicks', ['partner_code' => 'LINK1']);
    }
}
