<?php

namespace Database\Factories;

use App\Models\TrainingArtifact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingArtifact>
 */
class TrainingArtifactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'html' => '<h1>'.fake()->sentence().'</h1><p>'.fake()->paragraph().'</p>',
        ];
    }
}
