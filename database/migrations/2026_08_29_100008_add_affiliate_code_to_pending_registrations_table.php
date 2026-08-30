<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carries the referral cookie's partner code from the public
 * registration form through to the webhook that finally creates the
 * Business (which has no cookie of its own).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->string('affiliate_code', 20)->nullable()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn('affiliate_code');
        });
    }
};
