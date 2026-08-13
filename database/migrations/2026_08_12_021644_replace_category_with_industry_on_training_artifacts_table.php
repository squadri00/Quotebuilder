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
        Schema::table('training_artifacts', function (Blueprint $table) {
            $table->foreignId('industry_id')->nullable()->after('title')->constrained()->nullOnDelete();
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_artifacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('industry_id');
            $table->string('category')->nullable()->after('title');
        });
    }
};
