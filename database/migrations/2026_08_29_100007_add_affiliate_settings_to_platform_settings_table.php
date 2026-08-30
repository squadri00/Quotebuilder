<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referral-program knobs, on the existing single-row platform_settings
 * store (same pattern as Stripe / mail / turnstile settings).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->boolean('affiliate_program_enabled')->default(true);
            $table->decimal('affiliate_default_commission_rate', 6, 3)->default(20);
            $table->unsignedSmallInteger('affiliate_claim_days')->default(30);
            $table->boolean('affiliate_claim_renew_on_activity')->default(true);
            $table->unsignedSmallInteger('affiliate_cookie_days')->default(45);
            $table->decimal('affiliate_min_payout', 10, 2)->default(50);
            $table->boolean('affiliate_auto_approve_partners')->default(false);
            $table->boolean('affiliate_auto_approve_referrals')->default(false);
            $table->string('affiliate_payout_currency', 3)->nullable()->comment('blank = default_currency');
            $table->string('affiliate_terms_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'affiliate_program_enabled',
                'affiliate_default_commission_rate',
                'affiliate_claim_days',
                'affiliate_claim_renew_on_activity',
                'affiliate_cookie_days',
                'affiliate_min_payout',
                'affiliate_auto_approve_partners',
                'affiliate_auto_approve_referrals',
                'affiliate_payout_currency',
                'affiliate_terms_url',
            ]);
        });
    }
};
