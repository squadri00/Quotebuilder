<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Code the Super Admin wants on every page: shown just before </head> and just before </body>.
            $table->text('head_code')->nullable();
            $table->text('body_code')->nullable();
            $table->boolean('custom_code_enabled')->default(true);
            // Which parts of the site get it. null (never saved) means the public pages and the sign-in/sign-up pages.
            $table->json('custom_code_areas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['head_code', 'body_code', 'custom_code_enabled', 'custom_code_areas']);
        });
    }
};
