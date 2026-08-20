<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The '/' route boots the app, which reads platform_settings on every
     * request (see AppServiceProvider::applyPlatformSettings) — that table
     * has to actually exist, so this needs a migrated database like every
     * other feature test.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
