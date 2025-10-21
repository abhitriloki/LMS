<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'certificate_number' => 'CERT-' . strtoupper($this->faker->bothify('????????')) . '-' . date('Y') . '-' . $this->faker->numberBetween(1000, 9999),
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'enrollment_id' => Enrollment::factory(),
            'template_id' => CertificateTemplate::factory(),
            'file_path' => 'certificates/' . $this->faker->uuid . '.pdf',
            'qr_code_path' => 'qr-codes/' . $this->faker->uuid . '.png',
            'issued_at' => now(),
            'expires_at' => null,
            'is_valid' => true,
            'is_emailed' => false,
            'emailed_at' => null,
            'download_count' => 0,
            'last_downloaded_at' => null,
        ];
    }

    /**
     * Indicate that the certificate is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(30),
            'is_valid' => true,
        ]);
    }

    /**
     * Indicate that the certificate is expiring soon
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(15),
            'is_valid' => true,
        ]);
    }

    /**
     * Indicate that the certificate is revoked
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_valid' => false,
        ]);
    }

    /**
     * Indicate that the certificate has been emailed
     */
    public function emailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_emailed' => true,
            'emailed_at' => now(),
        ]);
    }
}
