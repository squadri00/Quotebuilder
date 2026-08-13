<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Option>
 */
class OptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'label' => fake()->word(),
            'price_modifier' => fake()->randomFloat(2, 0, 20),
        ];
    }

    /**
     * Keep business_id in sync with whichever question this option ends
     * up belonging to (default or explicitly passed via ->for()).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Option $option) {
            $option->business_id ??= $option->question->business_id;
        });
    }
}
