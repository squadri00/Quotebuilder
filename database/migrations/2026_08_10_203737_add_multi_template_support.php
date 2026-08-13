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
        // Alongside the existing singular template_business_id (kept, and
        // still set to whichever template was picked first — nothing that
        // already reads that column breaks), this holds the full list
        // when a business picks more than one template at signup.
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->json('template_business_ids')->nullable()->after('template_business_id');
        });

        // Accurate record of every template a business was actually built
        // from — businesses.created_from_template_id is kept too (the
        // first one picked, for the existing single-template display),
        // but this is what lets Super Admin show the true, possibly
        // multi-template picture.
        Schema::create('business_template', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('businesses')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_template');

        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn('template_business_ids');
        });
    }
};
