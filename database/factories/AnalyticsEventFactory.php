<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    public function definition(): array
    {
        $eventTypes = [
            'course_view',
            'lesson_view',
            'enrollment',
            'course_completion',
            'assessment_attempt',
            'login',
            'logout'
        ];

        $eventCategories = [
            'course',
            'content',
            'assessment',
            'auth',
            'general'
        ];

        return [
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'event_category' => $this->faker->randomElement($eventCategories),
            'event_data' => [
                'key' => $this->faker->word,
                'value' => $this->faker->word,
            ],
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'session_id' => $this->faker->uuid,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function courseView(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'course_view',
            'event_category' => 'course',
            'event_data' => [
                'course_id' => $this->faker->numberBetween(1, 100),
                'course_title' => $this->faker->sentence(3),
            ],
        ]);
    }

    public function lessonView(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'lesson_view',
            'event_category' => 'content',
            'event_data' => [
                'lesson_id' => $this->faker->numberBetween(1, 100),
                'lesson_title' => $this->faker->sentence(3),
                'module_id' => $this->faker->numberBetween(1, 50),
            ],
        ]);
    }

    public function enrollment(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'enrollment',
            'event_category' => 'course',
            'event_data' => [
                'course_id' => $this->faker->numberBetween(1, 100),
                'enrollment_type' => $this->faker->randomElement(['self', 'mandatory', 'assigned']),
            ],
        ]);
    }

    public function courseCompletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'course_completion',
            'event_category' => 'course',
            'event_data' => [
                'course_id' => $this->faker->numberBetween(1, 100),
                'final_score' => $this->faker->randomFloat(2, 0, 100),
                'completion_date' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function assessmentAttempt(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'assessment_attempt',
            'event_category' => 'assessment',
            'event_data' => [
                'assessment_id' => $this->faker->numberBetween(1, 50),
                'score' => $this->faker->randomFloat(2, 0, 100),
                'passed' => $this->faker->boolean,
            ],
        ]);
    }
}
