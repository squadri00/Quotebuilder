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
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name');
            $table->string('company_name');
            $table->string('email');
            $table->string('password');
            $table->foreignId('template_business_id')->nullable();
            $table->string('country')->nullable();
            $table->string('state_province')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained()->cascadeOnDelete();
            // Set by the webhook once it actually creates the real
            // Business — lets the success/waiting page (and the checkout
            // page itself, to block re-use) tell a finalized registration
            // from one still awaiting payment.
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
