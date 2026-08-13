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
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('role');
        });

        // The rigid Owner/Admin/Member split is replaced by Owner (fixed,
        // full access) + Member (fully custom: a permissions list plus
        // per-product grants). An existing 'admin' user had blanket access
        // to everything Member can now be granted piecemeal — preserve
        // that by explicitly granting all of it, rather than silently
        // narrowing what they can do.
        $admins = DB::table('users')->where('role', 'admin')->get(['id', 'business_id']);

        foreach ($admins as $admin) {
            DB::table('users')->where('id', $admin->id)->update([
                'role' => 'member',
                'permissions' => json_encode(['tax_rates', 'business_settings', 'announcements', 'support']),
            ]);

            $productIds = DB::table('products')->where('business_id', $admin->business_id)->pluck('id');
            $now = now();

            foreach ($productIds as $productId) {
                DB::table('product_user')->insertOrIgnore([
                    'product_id' => $productId,
                    'user_id' => $admin->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
