<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'report_type' => $this->faker->randomElement([
                'user_activity',
                'course_completion',
                'department_performance',
                'compliance',
                'assessment_results',
                'enrollment_summary',
                'certificate_issuance',
                'learning_hours'
            ]),
            'parameters' => [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ],
            'generated_by' => User::factory(),
            'generated_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'file_path' => $this->faker->optional()->filePath(),
            'file_format' => $this->faker->randomElement(['pdf', 'excel', 'csv']),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'scheduled' => false,
            'schedule_config' => null,
            'last_run_at' => null,
            'next_run_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'generated_at' => now(),
            'file_path' => 'reports/report_' . $this->faker->uuid . '.pdf',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'generated_at' => null,
            'file_path' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'generated_at' => null,
            'file_path' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled' => true,
            'schedule_config' => [
                'frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly']),
                'enabled' => true,
            ],
            'next_run_at' => now()->addDay(),
        ]);
    }
}
