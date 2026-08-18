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
        Schema::create('tax_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // Which named Cashier subscription this invoice belonged to
            // ('default' or 'support') — informational only, not used for
            // any access-control decision.
            $table->string('subscription_type')->nullable();

            // Unique so a redelivered webhook (Stripe's normal retry
            // behaviour) can never double-record the same invoice's tax.
            $table->string('stripe_invoice_id')->unique();

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->timestamp('collected_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_collections');
    }
};
