<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Purely a display layer for the public /pricing page — a "was" price to
 * show struck through next to the real one, with the savings shown as
 * either a dollar amount or a percentage. Deliberately never touches
 * stripe_price_id or the existing price column: the discount is always
 * computed live from compare_at_price minus the real price (see
 * Plan::discountAmount()/discountPercent()), so what's displayed can
 * never drift out of sync with what Stripe actually charges.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('compare_at_price', 8, 2)->nullable()->after('price');
            $table->boolean('show_discount')->default(false)->after('compare_at_price');
            $table->string('discount_display')->default('percentage')->after('show_discount');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['compare_at_price', 'show_discount', 'discount_display']);
        });
    }
};
