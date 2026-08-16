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
        Schema::table('products', function (Blueprint $table) {
            // Whether this product appears on the business's "Quote Hub" —
            // a single public page/embed listing several of a business's
            // products as a picker, so a customer can pick which one they
            // want a quote for. Hand-picked per product rather than
            // defaulting to "every active product" so a business can keep
            // niche/internal-only products off their public-facing hub.
            $table->boolean('show_in_quote_hub')->default(false)->after('is_active');

            // Only meaningful when show_in_quote_hub is true — nulled out
            // otherwise so a product removed from the hub doesn't carry a
            // stale position if it's ever re-added later.
            $table->unsignedInteger('quote_hub_sort_order')->nullable()->after('show_in_quote_hub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['show_in_quote_hub', 'quote_hub_sort_order']);
        });
    }
};
