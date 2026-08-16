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
            // logo_path (pre-existing) is now specifically the LIGHT-mode
            // logo, kept under its original name so whatever's already
            // uploaded there keeps working without a data migration. This
            // is its dark-mode counterpart — optional: when left blank,
            // every dark-mode surface just keeps showing the light logo
            // rather than going blank.
            $table->string('dark_logo_path')->nullable()->after('logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('dark_logo_path');
        });
    }
};
