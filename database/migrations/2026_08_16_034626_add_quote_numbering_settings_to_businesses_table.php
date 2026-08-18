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
        Schema::table('businesses', function (Blueprint $table) {
            // How this business's own quote reference numbers look — see
            // Business::nextQuoteReferenceNumber(). Purely cosmetic/
            // business-facing; the database's own quotes.id stays the
            // real internal primary key regardless of this setting.
            $table->string('quote_number_format')->default('numeric'); // 'numeric' or 'alphanumeric'
            $table->string('quote_number_prefix')->nullable(); // only used when format is 'alphanumeric'

            // The next number to assign — incremented (inside a locked
            // transaction) every time a quote is created. A business can
            // change this in Business Settings at any time, e.g. to match
            // numbering they already used before switching to this app.
            $table->unsignedInteger('quote_number_next')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['quote_number_format', 'quote_number_prefix', 'quote_number_next']);
        });
    }
};
