<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminCountriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_create_and_edit_countries(): void
    {
        $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

        $this->actingAs($admin, 'admin')->get(route('superadmin.countries.index'))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('superadmin.countries.create'))->assertOk();

        $this->actingAs($admin, 'admin')->post(route('superadmin.countries.store'), [
            'name' => 'Australia',
            'short_code' => 'au',
            'currency_code' => 'aud',
            'currency_symbol' => 'A$',
            'currency_position' => 'before',
            'is_active' => '1',
        ])->assertRedirect(route('superadmin.countries.index'));

        $country = Country::where('name', 'Australia')->first();
        $this->assertNotNull($country);
        $this->assertSame('AU', $country->short_code, 'short_code should be uppercased');
        $this->assertSame('AUD', $country->currency_code, 'currency_code should be uppercased');

        $this->actingAs($admin, 'admin')->get(route('superadmin.countries.edit', $country))->assertOk();

        $this->actingAs($admin, 'admin')->patch(route('superadmin.countries.toggle-active', $country))
            ->assertRedirect();
        $this->assertFalse($country->fresh()->is_active);
    }
}
