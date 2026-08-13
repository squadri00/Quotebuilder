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
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            // Nullable: a rule can apply to one specific product, or be
            // business-wide (e.g. "orders over $500 get free shipping").
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('condition_logic');
            $table->json('action_logic');

            // Reserved for future use — see docs/conventions.md.
            $table->string('attrib1')->nullable();
            $table->string('attrib2')->nullable();
            $table->string('attrib3')->nullable();
            $table->decimal('num1', 10, 2)->nullable();
            $table->decimal('num2', 10, 2)->nullable();
            $table->decimal('num3', 10, 2)->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
