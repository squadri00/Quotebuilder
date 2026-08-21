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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('description', 160)->nullable()->after('question_text');
        });

        Schema::table('options', function (Blueprint $table) {
            $table->string('description', 160)->nullable()->after('label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
