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
        Schema::create('platform_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->default('CA');
            // null = the country-wide default rate for any province not
            // explicitly listed (e.g. "5% GST" for every Canadian province
            // other than Ontario, without needing one row per province).
            $table->string('province', 60)->nullable();
            $table->string('tax_label', 20);
            $table->decimal('rate', 6, 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_tax_rates');
    }
};
