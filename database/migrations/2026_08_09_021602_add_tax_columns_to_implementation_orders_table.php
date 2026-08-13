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
        Schema::table('implementation_orders', function (Blueprint $table) {
            $table->string('tax_label', 20)->nullable()->after('price');
            $table->decimal('tax_rate', 6, 3)->nullable()->after('tax_label');
            $table->decimal('tax_amount', 8, 2)->default(0)->after('tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('implementation_orders', function (Blueprint $table) {
            $table->dropColumn(['tax_label', 'tax_rate', 'tax_amount']);
        });
    }
};
