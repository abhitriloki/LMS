<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'enrollment_type' => $this->faker->randomElement(['self', 'mandatory', 'assigned']),
            'enrolled_by' => null,
            'deadline' => null,
            'status' => 'active',
            'completion_date' => null,
            'final_score' => null,
            'certificate_id' => null,
            'progress_percentage' => 0,
            'last_accessed_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completion_date' => now(),
            'progress_percentage' => 100,
            'final_score' => $this->faker->randomFloat(2, 70, 100),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'progress_percentage' => $this->faker->numberBetween(1, 99),
        ]);
    }

    public function mandatory(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_type' => 'mandatory',
            'deadline' => now()->addDays(30),
        ]);
    }
}
