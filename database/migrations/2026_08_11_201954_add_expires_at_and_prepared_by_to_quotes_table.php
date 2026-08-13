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
            $table->timestamp('expires_at')->nullable()->after('emailed_at');
            $table->string('prepared_by_name')->nullable()->after('expires_at');
            $table->string('prepared_by_email')->nullable()->after('prepared_by_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'prepared_by_name', 'prepared_by_email']);
        });
    }
};
