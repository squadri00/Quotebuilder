<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Rule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rule>
 */
class RuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'product_id' => null,
            'name' => fake()->words(3, true),
            'condition_logic' => ['operator' => 'equals', 'field' => 'example', 'value' => true],
            'action_logic' => ['operator' => 'add', 'amount' => 0],
        ];
    }

    /**
     * If a product is attached, keep business_id in sync with it.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Rule $rule) {
            if ($rule->product_id) {
                $rule->business_id = $rule->product->business_id;
            }
        });
    }
}
