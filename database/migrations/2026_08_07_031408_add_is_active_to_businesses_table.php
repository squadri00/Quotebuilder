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
            // Platform-level kill switch, separate from a product's own
            // is_active. A deactivated business's users can't log in and
            // their public quote builder stops working — see
            // App\Http\Middleware\EnsureBusinessIsActive.
            $table->boolean('is_active')->default(true)->after('is_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
