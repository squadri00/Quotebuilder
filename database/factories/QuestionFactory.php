<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
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
            'question_text' => fake()->sentence().'?',
            'type' => fake()->randomElement(['single_choice', 'number', 'text']),
            'sort_order' => 0,
        ];
    }

    /**
     * Keep business_id in sync with whichever product this question ends
     * up belonging to (default or explicitly passed via ->for()).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Question $question) {
            $question->business_id ??= $question->product->business_id;
        });
    }
}
