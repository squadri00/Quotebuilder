<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained('affiliate_partners')->nullOnDelete();
            $table->string('partner_code', 20);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('landing_path')->nullable();
            $table->string('referrer')->nullable();
            $table->foreignId('converted_business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->timestamps();

            $table->index('partner_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
    }
};
