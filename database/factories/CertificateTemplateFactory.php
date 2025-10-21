<?php

namespace Database\Factories;

use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateTemplateFactory extends Factory
{
    protected $model = CertificateTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Template',
            'description' => $this->faker->sentence(),
            'html_template' => $this->getDefaultTemplate(),
            'variables' => [
                'certificate_number',
                'user_name',
                'course_title',
                'issued_date',
                'completion_date',
                'final_score',
            ],
            'orientation' => 'landscape',
            'page_size' => 'A4',
            'is_default' => false,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the template is the default template
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the template is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Get default certificate HTML template
     */
    protected function getDefaultTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }
        .certificate-title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .recipient-name {
            font-size: 28px;
            margin: 20px 0;
        }
        .course-title {
            font-size: 24px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="certificate-title">Certificate of Completion</div>
    <div class="recipient-name">{{user_name}}</div>
    <div>has successfully completed</div>
    <div class="course-title">{{course_title}}</div>
    <div>Certificate Number: {{certificate_number}}</div>
    <div>Issued: {{issued_date}}</div>
</body>
</html>
HTML;
    }
}
