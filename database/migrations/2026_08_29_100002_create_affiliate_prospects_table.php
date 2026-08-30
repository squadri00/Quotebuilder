<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The prospect claim registry — the anti-overlap core. One active claim
 * per client at a time (enforced in ProspectService, not a DB unique key,
 * because "same client" is a fuzzy normalised match, not one column).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('affiliate_partners')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('company_name_norm')->comment('Lowercased / de-punctuated for matching');
            $table->string('contact_name', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('phone_norm', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('email_norm', 150)->nullable();
            $table->string('website')->nullable();
            $table->string('domain_norm', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_province', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->foreignId('estimated_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->enum('status', ['open', 'working', 'won', 'lost', 'expired', 'released'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamp('claim_expires_at');
            $table->foreignId('converted_business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->comment('admins.id, or null for auto-expiry');
            $table->timestamps();

            $table->index('status');
            $table->index('claim_expires_at');
            $table->index('company_name_norm');
            $table->index('phone_norm');
            $table->index('email_norm');
            $table->index('domain_norm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_prospects');
    }
};
