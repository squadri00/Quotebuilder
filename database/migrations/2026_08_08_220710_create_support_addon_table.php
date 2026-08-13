<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Singleton settings row for the standalone Priority Support
     * subscription — independent of Plan tiers, configured once by the
     * Super Admin. A single seeded row is inserted here so the app never
     * has to handle a "no config exists yet" case.
     */
    public function up(): void
    {
        Schema::create('support_addon', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Priority Support');
            $table->decimal('price', 8, 2)->default(9.99);
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        DB::table('support_addon')->insert([
            'name' => 'Priority Support',
            'price' => 9.99,
            'stripe_price_id' => null,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_addon');
    }
};
