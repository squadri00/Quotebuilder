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
            // The platform operator's full legal entity name (e.g. "Eformics
            // Systems") — distinct from platform_name, which is the short
            // brand shown throughout the UI (e.g. "Runwrk"). Used wherever
            // the legal entity, not the brand, needs to appear: copyright
            // lines, "a product of" credits, future invoices/receipts.
            $table->string('legal_business_name')->nullable()->after('platform_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('legal_business_name');
        });
    }
};
