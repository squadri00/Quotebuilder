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
            // A PHP timezone identifier (e.g. "America/Toronto"). Null means
            // "not set yet" — falls back to config('app.timezone') wherever
            // it's read, same as every other optional business setting.
            $table->string('timezone')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
