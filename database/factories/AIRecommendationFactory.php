<?php

namespace Database\Factories;

use App\Models\AIRecommendation;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIRecommendationFactory extends Factory
{
    protected $model = AIRecommendation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'recommendation_type' => 'ai_generated',
            'score' => $this->faker->randomFloat(2, 0.5, 1.0),
            'reasoning' => $this->faker->sentence(15),
            'status' => 'active',
            'feedback' => null,
            'expires_at' => now()->addDays(30),
        ];
    }

    /**
     * Indicate that the recommendation is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'feedback' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the recommendation is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'feedback' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the recommendation is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate a high score recommendation.
     */
    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->randomFloat(2, 0.85, 1.0),
        ]);
    }

    /**
     * Indicate a low score recommendation.
     */
    public function lowScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->randomFloat(2, 0.5, 0.7),
        ]);
    }
}
