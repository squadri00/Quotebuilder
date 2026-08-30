<?php

namespace Database\Seeders;

use App\Models\AffiliatePartner;
use App\Models\Business;
use App\Models\TaxCollection;
use App\Services\Affiliate\AttributionService;
use App\Services\Affiliate\CommissionService;
use App\Services\Affiliate\ProspectService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for the referral partner program. Idempotent-ish: partners
 * are matched by email, so re-running won't duplicate them.
 *
 *   php artisan db:seed --class=AffiliateDemoSeeder
 */
class AffiliateDemoSeeder extends Seeder
{
    public function run(): void
    {
        // This test DB's platform default_currency is USD but its Stripe
        // charges (tax_collections) are CAD — pin payouts to CAD so the
        // demo commissions actually accrue.
        \App\Models\PlatformSetting::get()->update(['affiliate_payout_currency' => 'CAD']);

        $jordan = AffiliatePartner::firstOrCreate(
            ['email' => 'partner.jordan@example.com'],
            [
                'partner_code' => 'JORDANQB1',
                'name' => 'Jordan Wells',
                'password' => Hash::make('partner1234'),
                'phone' => '+1 403 555 0101',
                'company_name' => 'Wells Growth Co.',
                'city' => 'Calgary', 'state_province' => 'AB', 'country' => 'Canada',
                'commission_rate' => 25,
                'payout_method' => 'paypal',
                'payout_details' => 'jordan@wellsgrowth.example',
                'status' => 'active',
                'agreement_accepted_at' => now(),
                'approved_at' => now(),
            ]
        );

        $sam = AffiliatePartner::firstOrCreate(
            ['email' => 'partner.sam@example.com'],
            [
                'partner_code' => 'SAMQB2',
                'name' => 'Sam Ortega',
                'password' => Hash::make('partner1234'),
                'city' => 'Vancouver', 'state_province' => 'BC', 'country' => 'Canada',
                'commission_rate' => 20,
                'status' => 'pending',
                'agreement_accepted_at' => now(),
            ]
        );

        $prospects = app(ProspectService::class);
        foreach ([
            [$jordan->id, ['company_name' => 'Northside Cabinets', 'phone' => '403 555 0777',
                'email' => 'info@northsidecabinets.example', 'website' => 'northsidecabinets.example',
                'city' => 'Calgary', 'state_province' => 'AB', 'industry' => 'Millwork']],
            [$jordan->id, ['company_name' => 'Redwood Fabrication', 'phone' => '403 555 0912',
                'city' => 'Airdrie', 'state_province' => 'AB', 'industry' => 'Fabrication']],
            [$sam->id, ['company_name' => 'Harbour Remodels', 'phone' => '604 555 0333',
                'city' => 'Burnaby', 'state_province' => 'BC', 'industry' => 'Remodeling']],
            // Deliberate overlap — Sam tries to claim what Jordan already has.
            [$sam->id, ['company_name' => 'North Side Cabinets Ltd', 'phone' => '4035550777',
                'city' => 'Calgary', 'state_province' => 'AB']],
        ] as [$pid, $data]) {
            $res = $prospects->claim($pid, $data);
            $this->command?->info(($res['ok'] ? '  claimed: ' : '  BLOCKED: ') . $data['company_name']
                . ($res['ok'] ? '' : ' — ' . $res['error']));
        }

        // Attach two businesses to Jordan; synthesise a year of monthly
        // platform payments so statements have something to work with.
        $targets = Business::orderBy('id')->limit(2)->get();
        $attribution = app(AttributionService::class);
        $commissions = app(CommissionService::class);

        foreach ($targets as $i => $biz) {
            $approve = $i === 0;
            $r = $attribution->attachManual($biz->id, $jordan->id, null, $approve, null, 'Demo seed');
            $this->command?->info("  {$biz->name} -> Jordan : "
                . ($r['ok'] ? "referral #{$r['referral']->id} (" . ($approve ? 'approved' : 'pending') . ')' : $r['error']));

            for ($mth = 1; $mth <= 12; $mth++) {
                $when = now()->subMonths($mth)->startOfMonth()->addDays(4);
                TaxCollection::firstOrCreate(
                    ['stripe_invoice_id' => "demo_seed_{$biz->id}_{$when->format('Ym')}"],
                    [
                        'business_id' => $biz->id,
                        'subscription_type' => 'default',
                        'amount' => 0,
                        'base_amount' => 49.00,
                        'total_amount' => 49.00,
                        'currency' => 'CAD',
                        'collected_at' => $when,
                    ]
                );
            }

            $commissions->accrueForBusiness($biz->id);
        }

        $this->command?->info('Partner logins: partner.jordan@example.com / partner.sam@example.com  (pw partner1234)');
    }
}
