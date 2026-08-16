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
        Schema::create('business_training_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // A private copy tied to exactly one of the business's own
            // products, one-to-one. Cascade-deletes with the product so
            // removing/replacing a product (however it's deleted — the
            // Templates page's Remove button, or a plan-limit Replace)
            // always cleans up its tutorial too, without every deletion
            // path needing to remember to do it separately.
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique('product_id');

            // The master this was copied from, for reference only — never
            // read from live. If Super Admin deletes the master later,
            // this just unlinks; the business keeps its own copy.
            $table->foreignId('source_artifact_id')->nullable()
                ->constrained('training_artifacts')->nullOnDelete();

            $table->string('title');
            $table->longText('html');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_training_artifacts');
    }
};
