<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Unique per business, not globally — two businesses can each
            // have a product slugged "flyers"; the URL disambiguates via
            // the business slug that comes before it.
            $table->string('slug')->nullable()->after('name');
            $table->unique(['business_id', 'slug']);
        });

        DB::table('products')->orderBy('id')->get(['id', 'business_id', 'name'])->each(function ($product) {
            $base = Str::slug($product->name) ?: 'product';
            $slug = $base;
            $suffix = 2;

            while (DB::table('products')->where('business_id', $product->business_id)->where('slug', $slug)->exists()) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
