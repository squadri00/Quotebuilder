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
        Schema::table('tax_collections', function (Blueprint $table) {
            // Originally just a tax ledger for HST/GST remittance — this
            // widens it into a general revenue ledger (still one row per
            // successful invoice) so the Super Admin Financial Activity
            // report has real base+total figures to sum, not just tax.
            $table->decimal('base_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('total_amount', 10, 2)->default(0)->after('base_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_collections', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'total_amount']);
        });
    }
};
