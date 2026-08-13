<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Option;
use App\Models\Product;
use App\Models\Question;
use App\Models\Rule;
use App\Models\User;
use App\Services\RulesEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RulesEngineTest extends TestCase
{
    use RefreshDatabase;

    private Product $flyers;

    private Question $paperType;

    private Option $matte;

    private Option $glossy;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::create(['name' => 'Printing Shop', 'industry' => 'Printing']);

        $this->actingAs(User::create([
            'business_id' => $business->id,
            'name' => 'Printing Shop Owner',
            'email' => 'owner@printingshop.test',
            'password' => bcrypt('password'),
        ]));

        $this->flyers = Product::create([
            'business_id' => $business->id,
            'name' => 'Flyers',
            'description' => 'Custom printed flyers.',
            'base_price' => 49.99,
            'is_active' => true,
        ]);

        $this->paperType = Question::create([
            'business_id' => $business->id,
            'product_id' => $this->flyers->id,
            'question_text' => 'Paper Type',
            'type' => 'single_choice',
            'sort_order' => 1,
        ]);

        $this->matte = Option::create([
            'business_id' => $business->id,
            'question_id' => $this->paperType->id,
            'label' => 'Matte',
            'price_modifier' => 0,
        ]);

        $this->glossy = Option::create([
            'business_id' => $business->id,
            'question_id' => $this->paperType->id,
            'label' => 'Glossy',
            'price_modifier' => 5,
        ]);
    }

    public function test_no_rules_triggered_returns_base_price_plus_option_price(): void
    {
        // A rule exists, but the customer's answer doesn't match it.
        Rule::create([
            'business_id' => $this->flyers->business_id,
            'product_id' => $this->flyers->id,
            'name' => 'Glossy surcharge',
            'condition_logic' => ['question_id' => $this->paperType->id, 'operator' => 'equals', 'value' => $this->glossy->id],
            'action_logic' => ['type' => 'add_fixed', 'amount' => 20],
        ]);

        $result = (new RulesEngine)->calculate($this->flyers->id, [
            $this->paperType->id => $this->matte->id,
        ]);

        $this->assertSame(49.99, $result['base_price']);
        $this->assertSame(49.99, $result['final_price']);
        $this->assertCount(0, $result['applied_rules']);
    }

    public function test_one_rule_triggered_adds_fixed_amount(): void
    {
        Rule::create([
            'business_id' => $this->flyers->business_id,
            'product_id' => $this->flyers->id,
            'name' => 'Glossy surcharge',
            'condition_logic' => ['question_id' => $this->paperType->id, 'operator' => 'equals', 'value' => $this->glossy->id],
            'action_logic' => ['type' => 'add_fixed', 'amount' => 20],
        ]);

        $result = (new RulesEngine)->calculate($this->flyers->id, [
            $this->paperType->id => $this->glossy->id,
        ]);

        // base 49.99 + glossy option modifier 5 + rule's fixed 20
        $this->assertSame(74.99, $result['final_price']);
        $this->assertCount(1, $result['applied_rules']);
        $this->assertSame('Glossy surcharge', $result['applied_rules'][0]['name']);
        $this->assertSame(20.0, $result['applied_rules'][0]['amount_changed']);
    }

    public function test_multiple_rules_triggered_stack_in_order(): void
    {
        Rule::create([
            'business_id' => $this->flyers->business_id,
            'product_id' => $this->flyers->id,
            'name' => 'Glossy surcharge',
            'condition_logic' => ['question_id' => $this->paperType->id, 'operator' => 'equals', 'value' => $this->glossy->id],
            'action_logic' => ['type' => 'add_fixed', 'amount' => 20],
        ]);

        Rule::create([
            'business_id' => $this->flyers->business_id,
            'product_id' => $this->flyers->id,
            'name' => 'Service fee',
            'condition_logic' => ['question_id' => $this->paperType->id, 'operator' => 'equals', 'value' => $this->glossy->id],
            'action_logic' => ['type' => 'add_percentage', 'percent' => 10],
        ]);

        $result = (new RulesEngine)->calculate($this->flyers->id, [
            $this->paperType->id => $this->glossy->id,
        ]);

        // base 49.99 + glossy 5 = 54.99
        // + fixed 20 = 74.99
        // + 10% of 74.99 = 7.499 -> 82.489
        $this->assertCount(2, $result['applied_rules']);
        $this->assertSame('Glossy surcharge', $result['applied_rules'][0]['name']);
        $this->assertSame('Service fee', $result['applied_rules'][1]['name']);
        $this->assertEqualsWithDelta(82.49, $result['final_price'], 0.01);
    }
}
