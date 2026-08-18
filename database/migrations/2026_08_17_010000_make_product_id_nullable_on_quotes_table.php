<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Was cascadeOnDelete() — deleting a product silently destroyed every
 * quote ever generated for it, permanently. A quote is a historical
 * record of a real transaction; it should survive the product it was
 * for being deleted or replaced, same as an old paper receipt doesn't
 * vanish when a store stops carrying an item. It just won't be able to
 * look up that product's live details anymore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
