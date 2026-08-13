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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('is_active');
            // The whole product tree (product + questions + options + rules)
            // as of the last time someone clicked "Publish". This — not the
            // live/draft rows — is what the public quote builder reads from,
            // so editing a published product never affects customers until
            // it's republished. See App\Models\Product::buildPublishableSnapshot().
            $table->json('published_snapshot')->nullable()->after('is_published');
            $table->timestamp('published_at')->nullable()->after('published_snapshot');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('sort_order');
        });

        Schema::table('options', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('price_modifier');
        });

        Schema::table('rules', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('action_logic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_published', 'published_snapshot', 'published_at']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });

        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });

        Schema::table('rules', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
