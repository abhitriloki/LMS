<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $eventTypes = [
            'created', 'updated', 'deleted', 'viewed',
            'login_success', 'login_failed', 'logout',
            'permission_changed', 'security_suspicious_activity'
        ];

        return [
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'auditable_type' => null,
            'auditable_id' => null,
            'description' => $this->faker->sentence(),
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'metadata' => null,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function withModel(string $modelType, int $modelId): static
    {
        return $this->state(fn (array $attributes) => [
            'auditable_type' => $modelType,
            'auditable_id' => $modelId,
        ]);
    }

    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'created',
            'description' => 'Model created',
        ]);
    }

    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'updated',
            'description' => 'Model updated',
            'old_values' => ['title' => 'Old Title'],
            'new_values' => ['title' => 'New Title'],
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'deleted',
            'description' => 'Model deleted',
        ]);
    }

    public function loginSuccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'login_success',
            'description' => 'User logged in successfully',
        ]);
    }

    public function loginFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'login_failed',
            'description' => 'Failed login attempt',
        ]);
    }
}
