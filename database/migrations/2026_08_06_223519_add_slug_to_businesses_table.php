<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Nullable + unique in one step (no ->change() needed). The app
            // always fills this in (see App\Models\Concerns\HasSlug), so in
            // practice it's never actually null once this migration backfills
            // existing rows below.
            $table->string('slug')->nullable()->unique()->after('name');
        });

        DB::table('businesses')->orderBy('id')->get(['id', 'name'])->each(function ($business) {
            $base = Str::slug($business->name) ?: 'business';
            $slug = $base;
            $suffix = 2;

            while (DB::table('businesses')->where('slug', $slug)->exists()) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            DB::table('businesses')->where('id', $business->id)->update(['slug' => $slug]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
