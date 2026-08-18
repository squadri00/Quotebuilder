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
            // The business-facing number (e.g. "42" or "INV-0042"),
            // formatted per the business's own quote_number_format at the
            // moment this quote was created — see Business::
            // nextQuoteReferenceNumber(). Nullable so quotes that predate
            // this feature just keep showing their plain #id, same as
            // always; every view falls back to that automatically.
            $table->string('reference_number')->nullable()->after('id');
            $table->unique(['business_id', 'reference_number']);

            // Set only on a quote created by revising an already-sent
            // one (see InternalQuoteController::save()) — points at the
            // original. The original is never touched or deleted; both
            // stay visible so there's a full history of what changed.
            $table->foreignId('revises_quote_id')->nullable()->after('reference_number')
                ->constrained('quotes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revises_quote_id');
            $table->dropUnique(['business_id', 'reference_number']);
            $table->dropColumn('reference_number');
        });
    }
};
