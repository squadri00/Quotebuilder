<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referral / affiliate partner accounts. Platform-level — completely
 * separate from `users` (business staff) and `admins` (super admins);
 * partners authenticate on their own `affiliate` guard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_partners', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 20)->unique()->comment('Referral code used in /r/{code} links');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 50)->nullable();
            $table->string('company_name')->nullable()->comment('"From" party on payout statements');
            $table->string('tax_id', 60)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->decimal('commission_rate', 6, 3)->default(20)->comment('Default commission percent');
            $table->string('payout_method', 40)->nullable();
            $table->text('payout_details')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->text('notes')->nullable()->comment('Internal super-admin notes');
            $table->timestamp('agreement_accepted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->comment('admins.id');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_partners');
    }
};
