<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('industry_id')->nullable()->after('industry')->constrained()->nullOnDelete();
        });

        // Every distinct free-text industry value already in use (e.g.
        // "Printing", "Landscaping", "Catering" on existing template rows)
        // becomes a real Industry record here, and every business/template
        // that had that text gets pointed at it — so nothing existing
        // breaks and nothing has to be manually re-entered in Super Admin.
        $distinctIndustries = DB::table('businesses')
            ->whereNotNull('industry')
            ->where('industry', '!=', '')
            ->distinct()
            ->pluck('industry');

        foreach ($distinctIndustries as $name) {
            $industryId = DB::table('industries')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('businesses')->where('industry', $name)->update(['industry_id' => $industryId]);
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('industry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('industry')->nullable();
        });

        DB::table('businesses')
            ->join('industries', 'businesses.industry_id', '=', 'industries.id')
            ->update(['businesses.industry' => DB::raw('industries.name')]);

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('industry_id');
        });

        Schema::dropIfExists('industries');
    }
};
