<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Option;
use App\Models\Product;
use App\Models\Question;
use App\Models\Rule;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds one realistic example business end-to-end (a print shop selling
 * flyers), so the product/question/option relationships can be verified
 * in Tinker. Safe to re-run — clears its own rows first.
 */
class PrintingShopDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::whereIn('email', ['owner@printingshop.test'])->delete();
        Business::where('name', 'Printing Shop')->delete();

        $business = Business::create([
            'name' => 'Printing Shop',
            'industry' => 'Printing',
            'is_template' => true,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Printing Shop Owner',
            'email' => 'owner@printingshop.test',
            'password' => bcrypt('password'),
        ]);

        $flyers = Product::create([
            'business_id' => $business->id,
            'name' => 'Flyers',
            'description' => 'Custom printed flyers for events and promotions.',
            'base_price' => 49.99,
            'is_active' => true,
        ]);

        $paperType = Question::create([
            'business_id' => $business->id,
            'product_id' => $flyers->id,
            'question_text' => 'Paper Type',
            'type' => 'single_choice',
            'sort_order' => 1,
        ]);

        $matte = Option::create([
            'business_id' => $business->id,
            'question_id' => $paperType->id,
            'label' => 'Matte',
            'price_modifier' => 0,
        ]);

        $glossy = Option::create([
            'business_id' => $business->id,
            'question_id' => $paperType->id,
            'label' => 'Glossy',
            'price_modifier' => 7.5,
        ]);

        Rule::create([
            'business_id' => $business->id,
            'product_id' => $flyers->id,
            'name' => 'Glossy surcharge',
            'condition_logic' => ['question_id' => $paperType->id, 'operator' => 'equals', 'value' => $glossy->id],
            'action_logic' => ['type' => 'add_fixed', 'amount' => 25],
        ]);

        Rule::create([
            'business_id' => $business->id,
            'product_id' => $flyers->id,
            'name' => 'Member discount on Matte',
            'condition_logic' => ['question_id' => $paperType->id, 'operator' => 'equals', 'value' => $glossy->id],
            'action_logic' => ['type' => 'set_option_price', 'option_id' => $matte->id, 'price_modifier' => 2],
        ]);

        $this->command?->info("Seeded Printing Shop (business id {$business->id}, template), user {$user->email} / password, product 'Flyers' (id {$flyers->id}) with a 'Paper Type' question, Matte/Glossy options, and 2 rules.");
    }
}
