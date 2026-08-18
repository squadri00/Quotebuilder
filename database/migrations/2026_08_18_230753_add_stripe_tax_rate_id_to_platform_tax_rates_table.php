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
        Schema::table('platform_tax_rates', function (Blueprint $table) {
            // A real Stripe Tax Rate object's ID — once set, this rate can
            // be attached to a subscription (Business::taxRates()) so
            // Stripe itemizes and re-applies it on every renewal invoice,
            // instead of the old approach of merging tax into a one-time
            // frozen price. Null until someone runs the one-time setup
            // that creates the matching object in Stripe for this row.
            $table->string('stripe_tax_rate_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_tax_rates', function (Blueprint $table) {
            $table->dropColumn('stripe_tax_rate_id');
        });
    }
};
