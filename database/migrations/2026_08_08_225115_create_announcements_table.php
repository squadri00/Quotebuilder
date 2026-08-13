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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            // QuoteBuilder has no trial/active business status like
            // Meccora — 'with_plan'/'no_plan' is the closest sensible
            // equivalent given what's actually on the businesses table.
            $table->enum('target_audience', ['all', 'with_plan', 'no_plan', 'specific'])->default('all');
            $table->foreignId('target_business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            $table->boolean('send_email')->default(false);
            // Set once emails go out, so editing an announcement never re-sends.
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
