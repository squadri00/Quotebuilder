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
        Schema::create('implementation_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('implementation_tier_id')->nullable()->constrained()->nullOnDelete();
            // Snapshotted at purchase time so editing/deleting a tier later
            // never changes what an existing order says it was bought as.
            $table->string('tier_name');
            $table->unsignedInteger('product_count');
            $table->decimal('price', 8, 2);
            $table->string('status')->default('awaiting_payment');
            $table->string('stripe_checkout_session_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('implementation_orders');
    }
};
