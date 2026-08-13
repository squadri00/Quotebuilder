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
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('address_line1')->nullable()->after('platform_name');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state_province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state_province');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('phone')->nullable()->after('country');
            $table->string('contact_email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('contact_email');
            $table->string('tax_account_info')->nullable()->after('website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'address_line1', 'address_line2', 'city', 'state_province',
                'postal_code', 'country', 'phone', 'contact_email', 'website', 'tax_account_info',
            ]);
        });
    }
};
