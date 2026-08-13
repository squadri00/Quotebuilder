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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->boolean('is_active')->default(true);

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
        Schema::dropIfExists('products');
    }
};
