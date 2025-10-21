<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'instructions' => fake()->paragraph(),
            'passing_score' => fake()->numberBetween(60, 80),
            'time_limit' => fake()->randomElement([0, 30, 60, 90, 120]),
            'max_attempts' => fake()->randomElement([0, 1, 2, 3]),
            'randomize_questions' => fake()->boolean(),
            'randomize_options' => fake()->boolean(),
            'show_results' => true,
            'show_correct_answers' => fake()->boolean(),
            'allow_review' => true,
            'is_published' => fake()->boolean(),
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
