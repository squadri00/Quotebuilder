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
            // Which template product this build sheet documents — always
            // the TEMPLATE's own product row (never a business's cloned
            // copy). Nullable: general reference material (e.g. "How to
            // Build a Rule") isn't about any one product. If the template
            // product itself is later deleted, this just unlinks rather
            // than deleting the training content.
            $table->foreignId('product_id')->nullable()->after('industry_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_artifacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
