<?php

namespace Database\Factories;

use App\Models\ContentAnalysis;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentAnalysisFactory extends Factory
{
    protected $model = ContentAnalysis::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'readability_score' => $this->faker->randomFloat(2, 50, 100),
            'engagement_score' => $this->faker->randomFloat(2, 50, 100),
            'overall_score' => $this->faker->randomFloat(2, 50, 100),
            'complexity_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'content_gaps' => [
                'missing_topics' => $this->faker->words(3),
                'progression_gaps' => $this->faker->words(2),
                'needs_more_depth' => $this->faker->words(2),
                'missing_prerequisites' => $this->faker->words(1),
            ],
            'suggestions' => [
                [
                    'category' => $this->faker->randomElement(['readability', 'structure', 'engagement', 'completeness']),
                    'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
                    'suggestion' => $this->faker->sentence(),
                    'impact' => $this->faker->sentence(),
                ],
            ],
            'accessibility_issues' => [
                [
                    'type' => $this->faker->randomElement(['missing_transcript', 'long_content', 'color_contrast']),
                    'severity' => $this->faker->randomElement(['high', 'medium', 'low']),
                    'lesson' => $this->faker->words(3, true),
                    'description' => $this->faker->sentence(),
                ],
            ],
            'is_current' => true,
            'analyzed_at' => now(),
        ];
    }

    public function withHighScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'readability_score' => $this->faker->randomFloat(2, 85, 100),
            'engagement_score' => $this->faker->randomFloat(2, 85, 100),
            'overall_score' => $this->faker->randomFloat(2, 85, 100),
        ]);
    }

    public function withLowScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'readability_score' => $this->faker->randomFloat(2, 40, 60),
            'engagement_score' => $this->faker->randomFloat(2, 40, 60),
            'overall_score' => $this->faker->randomFloat(2, 40, 60),
        ]);
    }

    public function withNoGaps(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_gaps' => [
                'missing_topics' => [],
                'progression_gaps' => [],
                'needs_more_depth' => [],
                'missing_prerequisites' => [],
            ],
        ]);
    }

    public function withNoAccessibilityIssues(): static
    {
        return $this->state(fn (array $attributes) => [
            'accessibility_issues' => [],
        ]);
    }

    public function notCurrent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => false,
        ]);
    }
}
