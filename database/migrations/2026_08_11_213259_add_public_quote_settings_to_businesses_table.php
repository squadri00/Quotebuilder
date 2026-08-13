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
            $table->unsignedSmallInteger('public_quote_validity_days')->nullable()->after('quotation_disclaimer');
            $table->boolean('public_pdf_download_enabled')->default(true)->after('public_quote_validity_days');
            $table->boolean('public_email_enabled')->default(true)->after('public_pdf_download_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['public_quote_validity_days', 'public_pdf_download_enabled', 'public_email_enabled']);
        });
    }
};
