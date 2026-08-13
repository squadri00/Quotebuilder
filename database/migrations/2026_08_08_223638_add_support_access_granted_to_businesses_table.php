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
        Schema::table('businesses', function (Blueprint $table) {
            // Lets the platform grant Priority Support to a business
            // directly (comped, VIP, paid outside Stripe, etc.) — same
            // "manual override regardless of Stripe" pattern already used
            // for plan_id.
            $table->boolean('support_access_granted')->default(false)->after('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('support_access_granted');
        });
    }
};
