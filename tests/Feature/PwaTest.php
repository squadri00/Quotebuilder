<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AffiliatePartner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * These are plain static files in public/ — the real webserver serves
     * them directly without ever reaching Laravel's router, so the test
     * HTTP kernel (which only knows registered routes) 404s on them. Read
     * them straight off disk instead; reachability itself is confirmed
     * manually against the dev server.
     */
    private function readPublicJson(string $filename): array
    {
        return json_decode(file_get_contents(public_path($filename)), true);
    }

    public function test_the_business_manifest_is_valid(): void
    {
        $json = $this->readPublicJson('manifest-business.json');

        // Relative (no leading slash) so they resolve correctly against the
        // manifest's own URL whether Quotaire is served from the domain
        // root (production) or a subpath like /quotebuilder/public (local
        // XAMPP dev, which has no dedicated vhost for this project).
        $this->assertSame('dashboard', $json['start_url']);
        $this->assertSame('.', $json['scope']);
        $this->assertSame('standalone', $json['display']);
        $this->assertCount(4, $json['icons']);
        foreach ($json['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_the_superadmin_manifest_is_valid(): void
    {
        $json = $this->readPublicJson('manifest-superadmin.json');

        $this->assertSame('superadmin/dashboard', $json['start_url']);
        $this->assertSame('superadmin/', $json['scope']);
        $this->assertSame('standalone', $json['display']);
        $this->assertCount(4, $json['icons']);
        foreach ($json['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_the_affiliate_manifest_is_valid(): void
    {
        $json = $this->readPublicJson('manifest-affiliate.json');

        $this->assertSame('affiliate', $json['start_url']);
        $this->assertSame('affiliate', $json['scope']);
        $this->assertSame('standalone', $json['display']);
        $this->assertCount(4, $json['icons']);
        foreach ($json['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_the_service_worker_file_exists_and_has_a_fetch_handler(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("addEventListener('fetch'", $sw);
        $this->assertStringContainsString("addEventListener('install'", $sw);
    }

    public function test_business_dashboard_links_the_manifest_registers_the_worker_and_offers_an_install_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('manifest-business.json', false)
            ->assertSee('navigator.serviceWorker.register(', false)
            ->assertSee('id="pwa-install-btn"', false)
            ->assertSee('mobileNavOpen', false)
            ->assertSee('beforeinstallprompt', false);
    }

    public function test_superadmin_dashboard_links_its_own_manifest_registers_the_worker_and_offers_an_install_button(): void
    {
        $admin = Admin::create(['name' => 'PWA Admin', 'email' => 'pwa-admin@e.com', 'password' => 'password12']);

        $this->actingAs($admin, 'admin')->get('/superadmin/dashboard')
            ->assertOk()
            ->assertSee('manifest-superadmin.json', false)
            ->assertSee('navigator.serviceWorker.register(', false)
            ->assertSee('id="pwa-install-btn"', false)
            ->assertSee('mobileNavOpen', false)
            ->assertSee('beforeinstallprompt', false);
    }

    public function test_affiliate_portal_links_its_own_manifest_registers_the_worker_and_offers_an_install_button(): void
    {
        $partner = AffiliatePartner::create([
            'partner_code' => 'PWA1', 'name' => 'PWA Partner', 'email' => 'pwa-partner@e.com',
            'password' => 'password12', 'commission_rate' => 20, 'status' => 'active',
        ]);

        $this->actingAs($partner, 'affiliate')->get('/affiliate')
            ->assertOk()
            ->assertSee('manifest-affiliate.json', false)
            ->assertSee('navigator.serviceWorker.register(', false)
            ->assertSee('id="pwa-install-btn"', false)
            ->assertSee('mobile-nav-sidebar', false)
            ->assertSee('beforeinstallprompt', false);
    }
}
