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
            // Where a customer's "Reply" on their quote email should land.
            // Not used as the SMTP From address (that stays the
            // platform's, so deliverability/SPF/DKIM keep working) — see
            // PublicQuoteController's quote emails.
            $table->string('notification_email')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('notification_email');
        });
    }
};
