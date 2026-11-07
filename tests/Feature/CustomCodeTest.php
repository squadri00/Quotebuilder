<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomCodeTest extends TestCase
{
    use RefreshDatabase;

    private const HEAD = '<meta name="wezable-verify" content="abc123">';

    private const BODY = '<script src="https://wezable.com/widget2.js" data-key="key123" async></script>';

    private function admin(): Admin
    {
        return Admin::firstOrCreate(['email' => 'admin@example.com'], ['name' => 'Test Admin', 'password' => 'password']);
    }

    private function save(array $over = [])
    {
        return $this->actingAs($this->admin(), 'admin')->patch(route('superadmin.settings.update'), $over + [
            'custom_code_form' => 1,
            'head_code' => self::HEAD,
            'body_code' => self::BODY,
            'custom_code_enabled' => 1,
            'custom_code_areas' => ['public', 'auth'],
        ]);
    }

    private function visitor(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
    }

    // ---------- the settings page ----------

    public function test_the_settings_page_has_the_custom_code_boxes(): void
    {
        $this->actingAs($this->admin(), 'admin')->get(route('superadmin.settings.edit'))->assertOk()
            ->assertSee('Custom code')->assertSee('Before the closing head tag', false)->assertSee('Before the closing body tag', false)
            ->assertSee('name="head_code"', false)->assertSee('name="body_code"', false)->assertSee('Custom code is on');
    }

    public function test_the_code_is_saved_and_the_page_shows_it_back(): void
    {
        $this->save()->assertSessionHasNoErrors()->assertRedirect(route('superadmin.settings.edit'));

        $s = PlatformSetting::get();
        $this->assertSame(self::HEAD, $s->head_code);
        $this->assertSame(self::BODY, $s->body_code);
        $this->assertTrue($s->custom_code_enabled);
        $this->assertSame(['public', 'auth'], $s->custom_code_areas);
        $this->actingAs($this->admin(), 'admin')->get(route('superadmin.settings.edit'))->assertSee('wezable-verify', false);
    }

    // ---------- where it appears ----------

    public function test_the_code_goes_just_before_the_closing_head_and_body_tags_of_the_public_site(): void
    {
        $this->save();
        $this->visitor();

        $html = $this->get('/')->assertOk()->getContent();
        $head = strpos($html, self::HEAD);
        $body = strpos($html, self::BODY);

        $this->assertNotFalse($head, 'the head code is raw, not escaped');
        $this->assertNotFalse($body);
        $this->assertLessThan(strpos($html, '</head>'), $head);
        $this->assertGreaterThan(strpos($html, '</head>'), $body, 'the script is not in the head');
        $this->assertLessThan(strpos($html, '</body>'), $body);
        $this->assertSame(1, substr_count($html, self::HEAD));
        $this->assertSame(1, substr_count($html, self::BODY));
        // nothing but whitespace between the code and the closing tag
        $this->assertMatchesRegularExpression('~'.preg_quote(self::BODY, '~').'\s*</body>~', $html);
        $this->assertMatchesRegularExpression('~'.preg_quote(self::HEAD, '~').'\s*</head>~', $html);
    }

    public function test_by_default_the_public_pages_and_the_sign_in_pages_get_it_but_the_dashboards_do_not(): void
    {
        $this->save(['custom_code_areas' => null]);
        $s = PlatformSetting::get();
        $this->assertSame([], $s->custom_code_areas, 'every box unticked means nowhere');

        PlatformSetting::get()->update(['custom_code_areas' => null]);
        $s = PlatformSetting::get()->fresh();
        $this->assertSame(self::HEAD, $s->customCode('head', 'public'));
        $this->assertSame(self::BODY, $s->customCode('body', 'auth'));
        $this->assertSame('', $s->customCode('head', 'app'));
        $this->assertSame('', $s->customCode('head', 'superadmin'));
    }

    public function test_the_sign_in_page_gets_it_and_the_super_admin_area_only_when_ticked(): void
    {
        $this->save();
        $this->visitor();
        $this->get('/login')->assertOk()->assertSee(self::HEAD, false)->assertSee(self::BODY, false);

        $this->actingAs($this->admin(), 'admin')->get(route('superadmin.dashboard'))->assertOk()->assertDontSee('wezable-verify', false);

        $this->save(['custom_code_areas' => ['superadmin']]);
        $this->actingAs($this->admin(), 'admin')->get(route('superadmin.dashboard'))->assertOk()->assertSee(self::HEAD, false)->assertSee(self::BODY, false);
        $this->visitor();
        $this->get('/')->assertOk()->assertDontSee('wezable-verify', false);
    }

    public function test_the_business_dashboard_layout_can_carry_it_too(): void
    {
        $this->save(['custom_code_areas' => ['app']]);
        $s = PlatformSetting::get();

        $this->assertSame(self::HEAD, $s->customCode('head', 'app'));
        $this->assertSame('', $s->customCode('head', 'public'));
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString("'where' => 'head', 'area' => 'app'", $layout);
        $this->assertStringContainsString("'where' => 'body', 'area' => 'app'", $layout);
    }

    public function test_every_layout_has_both_spots_in_the_right_place(): void
    {
        foreach (['public' => 'public', 'guest' => 'auth', 'app' => 'app', 'superadmin' => 'superadmin'] as $layout => $area) {
            $src = file_get_contents(resource_path("views/layouts/$layout.blade.php"));
            $this->assertMatchesRegularExpression("~partials\\.custom-code', \\['where' => 'head', 'area' => '$area'\\]\\)\\s*</head>~", $src, "$layout head");
            $this->assertMatchesRegularExpression("~partials\\.custom-code', \\['where' => 'body', 'area' => '$area'\\]\\)\\s*</body>~", $src, "$layout body");
        }
    }

    // ---------- off, empty, and safe ----------

    public function test_switching_it_off_removes_it_everywhere_without_deleting_it(): void
    {
        $this->save();
        $this->save(['custom_code_enabled' => null]);
        $this->visitor();

        $this->get('/')->assertOk()->assertDontSee('wezable-verify', false)->assertDontSee('widget2.js', false);
        $s = PlatformSetting::get();
        $this->assertFalse($s->custom_code_enabled);
        $this->assertSame(self::HEAD, $s->head_code, 'still saved');
    }

    public function test_with_nothing_entered_the_pages_have_no_extra_output(): void
    {
        $this->visitor();
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('wezable', $html);
        $this->assertMatchesRegularExpression('~\s*</head>~', $html);
    }

    public function test_saving_another_part_of_the_page_never_touches_the_code(): void
    {
        $this->save();
        $this->actingAs($this->admin(), 'admin')->patch(route('superadmin.settings.update'), ['platform_name' => 'Quotaire'])->assertSessionHasNoErrors();

        $s = PlatformSetting::get();
        $this->assertSame(self::HEAD, $s->head_code);
        $this->assertSame(self::BODY, $s->body_code);
        $this->assertTrue($s->custom_code_enabled);
        $this->assertSame(['public', 'auth'], $s->custom_code_areas);
    }

    public function test_code_that_would_close_or_reopen_the_page_is_refused(): void
    {
        $this->save();
        foreach (['</head>', '</body>', '<html lang="en">', '<body class="x">', '<HEAD>', '</ body>'] as $bad) {
            $this->save(['head_code' => 'x '.$bad])->assertSessionHasErrors('head_code');
            $this->save(['body_code' => 'x '.$bad])->assertSessionHasErrors('body_code');
        }
        $this->assertSame(self::HEAD, PlatformSetting::get()->head_code, 'nothing was changed');
    }

    public function test_the_size_and_the_areas_are_checked(): void
    {
        $this->save(['head_code' => str_repeat('a', 20001)])->assertSessionHasErrors('head_code');
        $this->save(['custom_code_areas' => ['nowhere']])->assertSessionHasErrors('custom_code_areas.0');
    }

    public function test_only_a_signed_in_super_admin_can_change_it(): void
    {
        $this->visitor();
        $this->patch(route('superadmin.settings.update'), ['custom_code_form' => 1, 'head_code' => '<script>bad()</script>'])->assertRedirect();
        $this->assertNull(PlatformSetting::get()->head_code);
    }

    public function test_changes_are_written_to_the_audit_log_but_a_plain_save_is_not(): void
    {
        $this->save()->assertSessionHasNoErrors();
        $this->assertSame(1, AuditLog::where('action', 'platform.custom_code')->count());

        $this->save(); // exactly the same again: nothing changed
        $this->assertSame(1, AuditLog::where('action', 'platform.custom_code')->count());

        $this->save(['head_code' => '<meta name="x" content="y">']);
        $this->assertSame(2, AuditLog::where('action', 'platform.custom_code')->count());
    }
}
