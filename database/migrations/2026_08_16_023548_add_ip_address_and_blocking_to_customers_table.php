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
        Schema::table('customers', function (Blueprint $table) {
            // The IP the most recent public quote submission came from —
            // not captured retroactively, so a customer created before
            // this existed just shows blank until they submit again. See
            // CustomerResolver::findOrCreate(). 45 chars comfortably fits
            // an IPv6 address.
            $table->string('ip_address', 45)->nullable()->after('email');

            // Blocks future public submissions from this customer's IP —
            // scoped to this business only, same as the customer record
            // itself. See PublicQuoteController::store().
            $table->boolean('is_blocked')->default(false)->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'is_blocked']);
        });
    }
};
