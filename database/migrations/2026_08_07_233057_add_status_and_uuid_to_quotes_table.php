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
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('status')->default('New')->after('final_price');
            // Always generated (see Quote::booted()), regardless of
            // whether the business currently has the quote_customer_link
            // feature — cheap to store, and means turning the feature on
            // later doesn't require backfilling old quotes. The route
            // that resolves it is what's actually gated.
            $table->uuid('uuid')->nullable()->unique()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['status', 'uuid']);
        });
    }
};
