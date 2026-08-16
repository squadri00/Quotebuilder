<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Cloudflare Turnstile — the "prove you're not a robot" widget
            // shown on public quote forms. Left blank, Turnstile simply
            // never renders and every submission passes through
            // unchecked, same as before this existed. See
            // App\Support\TurnstileVerifier.
            $table->string('turnstile_site_key')->nullable();
            $table->text('turnstile_secret_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['turnstile_site_key', 'turnstile_secret_key']);
        });
    }
};
