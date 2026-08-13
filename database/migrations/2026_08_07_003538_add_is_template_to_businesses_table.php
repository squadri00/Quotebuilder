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
            // A template business is a normal, fully-functioning business
            // (it can log in, edit its own catalog, etc.) that also serves
            // as a starting-point catalog new businesses can clone from at
            // registration. See App\Services\TemplateCloner.
            $table->boolean('is_template')->default(false)->after('industry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('is_template');
        });
    }
};
