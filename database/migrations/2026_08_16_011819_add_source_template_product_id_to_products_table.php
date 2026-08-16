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
            // Which TEMPLATE product this was cloned from (set by
            // TemplateCloner::clone(), never by hand) — lets the Templates
            // page tell "already installed" apart from "not installed yet"
            // instead of guessing from the name. Null for a business's own
            // hand-built products, which were never cloned from anything.
            // Self-referencing products.id, so it's nullable — if the
            // template product itself is later deleted, this just unlinks
            // rather than deleting a real business's own product.
            $table->foreignId('source_template_product_id')->nullable()
                ->after('business_id')->constrained('products')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_template_product_id');
        });
    }
};
