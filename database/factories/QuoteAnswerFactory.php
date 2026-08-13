<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Quote;
use App\Models\QuoteAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteAnswer>
 */
class QuoteAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_id' => Quote::factory(),
            'question_id' => Question::factory(),
            'option_id' => null,
            'answer_value' => fake()->word(),
        ];
    }

    /**
     * Keep business_id in sync with whichever quote this answer ends
     * up belonging to (default or explicitly passed via ->for()).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (QuoteAnswer $answer) {
            $answer->business_id ??= $answer->quote->business_id;
        });
    }
}
