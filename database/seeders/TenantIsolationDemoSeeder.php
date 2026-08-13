<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds two separate businesses, each with one user, as standing test
 * accounts for manually checking tenant isolation (log in as one, confirm
 * you can't see the other's data). Safe to re-run — clears its own rows first.
 */
class TenantIsolationDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::whereIn('email', ['owner-a@example.com', 'owner-b@example.com'])->delete();
        Business::whereIn('name', ['Business A', 'Business B'])->delete();

        $businessA = Business::create(['name' => 'Business A', 'industry' => 'Printing']);
        $userA = User::create([
            'business_id' => $businessA->id,
            'name' => 'Owner A',
            'email' => 'owner-a@example.com',
            'password' => bcrypt('password'),
        ]);

        $businessB = Business::create(['name' => 'Business B', 'industry' => 'Landscaping']);
        $userB = User::create([
            'business_id' => $businessB->id,
            'name' => 'Owner B',
            'email' => 'owner-b@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->command?->info("Seeded Business A (id {$businessA->id}, user id {$userA->id}) and Business B (id {$businessB->id}, user id {$userB->id}).");
    }
}
