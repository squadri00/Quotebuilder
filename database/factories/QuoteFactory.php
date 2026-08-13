<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'final_price' => fake()->randomFloat(2, 10, 500),
        ];
    }

    /**
     * Keep business_id in sync with whichever product this quote ends
     * up belonging to (default or explicitly passed via ->for()).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Quote $quote) {
            $quote->business_id ??= $quote->product->business_id;
        });
    }
}
