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
        Schema::create('demo_calculators', function (Blueprint $table) {
            $table->id();
            // The real template Business whose Quote Hub this card opens
            // (see PublicQuoteController::picker()) — cascades on delete
            // since a demo card pointing at a deleted business is
            // meaningless, not something worth keeping around orphaned.
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description');
            // SVG path "d" attribute data for the card's icon — picked
            // from a curated list (App\Support\DemoCalculatorIcons), never
            // hand-typed, so this is always safe, valid markup.
            $table->text('icon');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(999);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_calculators');
    }
};
