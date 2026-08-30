<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Confirmed partner -> business attribution. A business carries at most
 * one referring partner. Commissions accrue while status is pending, but
 * only become payable once a super admin approves the referral.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('affiliate_partners')->cascadeOnDelete();
            $table->foreignId('business_id')->unique()->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('prospect_id')->nullable()->constrained('affiliate_prospects')->nullOnDelete();
            $table->enum('source', ['link', 'prospect', 'manual'])->default('link');
            $table->decimal('commission_rate', 6, 3)->nullable()->comment('Per-referral override; null = partner default');
            $table->enum('status', ['pending', 'approved', 'rejected', 'ended'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason')->nullable();
            $table->timestamp('first_commission_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_referrals');
    }
};
