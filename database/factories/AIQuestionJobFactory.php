<?php

namespace Database\Factories;

use App\Models\AIQuestionJob;
use App\Models\CourseLesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIQuestionJobFactory extends Factory
{
    protected $model = AIQuestionJob::class;

    public function definition(): array
    {
        return [
            'lesson_id' => CourseLesson::factory(),
            'requested_by' => User::factory(),
            'status' => 'pending',
            'parameters' => [
                'count' => $this->faker->numberBetween(1, 10),
                'types' => ['multiple_choice', 'true_false'],
                'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
                'include_explanation' => true,
                'points_per_question' => 1,
            ],
            'questions_count' => null,
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'questions_count' => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'error_message' => 'Test error message',
        ]);
    }
}
