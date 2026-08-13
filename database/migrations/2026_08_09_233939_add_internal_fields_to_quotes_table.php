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
            // 'public' (customer-submitted, the default — every existing
            // row) or 'internal' (staff-created on a customer's behalf).
            $table->string('source')->default('public')->after('status');

            // Staff-only, never shown to the customer — special
            // instructions, negotiated context, etc.
            $table->text('internal_notes')->nullable()->after('meta');

            // What RulesEngine + TaxCalculator actually computed, kept
            // untouched even if a staff member overrides the price —
            // final_price becomes the real quoted amount either way, this
            // is the audit-trail reference. Null for 'public' quotes,
            // where there's never an override and final_price already
            // *is* the calculated price.
            $table->decimal('calculated_price', 10, 2)->nullable()->after('final_price');

            // Which staff member created this — null for 'public' quotes.
            $table->foreignId('created_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['source', 'internal_notes', 'calculated_price']);
        });
    }
};
