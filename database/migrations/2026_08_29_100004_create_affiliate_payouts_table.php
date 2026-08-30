<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monthly self-billed commission statement a partner generates and the
 * platform pays. Created before affiliate_commissions so the commission
 * table's payout_id FK can point at it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number', 40)->unique();
            $table->foreignId('partner_id')->constrained('affiliate_partners')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->string('currency', 3)->default('CAD');
            $table->unsignedInteger('commission_count')->default(0);
            $table->decimal('base_total', 12, 2)->default(0);
            $table->decimal('rate_snapshot', 6, 3)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'finalized', 'submitted', 'paid', 'void'])->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            // Identity snapshots taken at finalize time.
            $table->string('partner_name_snapshot')->nullable();
            $table->string('partner_email_snapshot', 150)->nullable();
            $table->string('partner_address_snapshot')->nullable();
            $table->string('partner_citystate_snapshot')->nullable();
            $table->string('partner_country_snapshot', 100)->nullable();
            $table->string('partner_tax_id_snapshot', 60)->nullable();
            $table->string('company_name_snapshot')->nullable();
            $table->string('company_address_snapshot')->nullable();
            $table->string('company_email_snapshot', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['partner_id', 'period_year', 'period_month', 'currency'], 'uq_affiliate_payouts_period');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payouts');
    }
};
