<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One accrual row per commissionable platform payment (a paid Stripe
 * subscription invoice, recorded in `tax_collections`), plus manual
 * adjustment / clawback lines. Idempotent on source_tax_collection_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained('affiliate_referrals')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('affiliate_partners')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('source_tax_collection_id')->nullable()
                ->constrained('tax_collections')->nullOnDelete();
            $table->string('business_name_snapshot')->default('');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('base_amount', 12, 2)->default(0)->comment('Ex-tax subtotal the % applies to');
            $table->string('currency', 3)->default('CAD');
            $table->decimal('rate', 6, 3)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->enum('kind', ['commission', 'adjustment', 'clawback'])->default('commission');
            $table->enum('status', ['pending', 'approved', 'on_payout', 'paid', 'void'])->default('pending');
            $table->foreignId('payout_id')->nullable()->constrained('affiliate_payouts')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->comment('admins.id for manual lines');
            $table->timestamps();

            $table->unique('source_tax_collection_id', 'uq_affiliate_commissions_source');
            $table->index(['partner_id', 'period_year', 'period_month']);
            $table->index('status');
            $table->index('payout_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
