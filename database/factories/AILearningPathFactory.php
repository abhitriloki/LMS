<?php

namespace Database\Factories;

use App\Models\AILearningPath;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AILearningPathFactory extends Factory
{
    protected $model = AILearningPath::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target_role' => $this->faker->randomElement([
                'Senior Developer',
                'Project Manager',
                'Data Analyst',
                'DevOps Engineer',
                'Team Lead',
            ]),
            'current_skills' => [
                $this->faker->randomElement(['PHP', 'JavaScript', 'Python', 'Java']),
                $this->faker->randomElement(['Laravel', 'React', 'Django', 'Spring']),
            ],
            'target_skills' => [
                $this->faker->randomElement(['System Architecture', 'Team Leadership', 'Advanced Programming']),
                $this->faker->randomElement(['Cloud Infrastructure', 'DevOps', 'Microservices']),
            ],
            'path_data' => [
                'reasoning' => $this->faker->sentence(),
                'courses' => [],
                'milestones' => [],
                'total_estimated_weeks' => $this->faker->numberBetween(4, 24),
            ],
            'estimated_duration' => $this->faker->numberBetween(4, 24),
            'status' => $this->faker->randomElement(['draft', 'active', 'paused', 'completed']),
            'progress_percentage' => $this->faker->numberBetween(0, 100),
            'started_at' => $this->faker->optional()->dateTimeBetween('-3 months', 'now'),
            'completed_at' => null,
            'last_adjusted_at' => null,
        ];
    }

    /**
     * Indicate that the learning path is in draft status
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'progress_percentage' => 0,
            'started_at' => null,
        ]);
    }

    /**
     * Indicate that the learning path is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'started_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    /**
     * Indicate that the learning path is paused
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
            'started_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    /**
     * Indicate that the learning path is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress_percentage' => 100,
            'started_at' => now()->subMonths(rand(1, 6)),
            'completed_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    /**
     * Add courses to the learning path
     */
    public function withCourses(array $courseIds): static
    {
        return $this->state(function (array $attributes) use ($courseIds) {
            $courses = [];
            foreach ($courseIds as $index => $courseId) {
                $courses[] = [
                    'course_id' => $courseId,
                    'order' => $index + 1,
                    'milestone' => $this->faker->randomElement(['Foundation', 'Intermediate', 'Advanced']),
                    'reason' => $this->faker->sentence(),
                    'estimated_weeks' => $this->faker->numberBetween(2, 8),
                ];
            }

            return [
                'path_data' => [
                    'reasoning' => $this->faker->sentence(),
                    'courses' => $courses,
                    'milestones' => [
                        [
                            'name' => 'Foundation Complete',
                            'course_ids' => array_slice($courseIds, 0, ceil(count($courseIds) / 2)),
                            'description' => 'Basic skills acquired',
                        ],
                    ],
                    'total_estimated_weeks' => count($courseIds) * 4,
                ],
                'estimated_duration' => count($courseIds) * 4,
            ];
        });
    }
}
