<?php

namespace Database\Factories;

use App\Models\AIGradingResult;
use App\Models\AttemptResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIGradingResultFactory extends Factory
{
    protected $model = AIGradingResult::class;

    public function definition(): array
    {
        $aiScore = $this->faker->randomFloat(2, 0, 10);
        $confidenceScore = $this->faker->randomFloat(2, 0.5, 1.0);
        $flaggedForReview = $confidenceScore < 0.7;

        return [
            'attempt_response_id' => AttemptResponse::factory(),
            'ai_score' => $aiScore,
            'ai_feedback' => $this->generateFeedback($aiScore),
            'confidence_score' => $confidenceScore,
            'rubric_scores' => $this->generateRubricScores(),
            'flagged_for_review' => $flaggedForReview,
            'review_reason' => $flaggedForReview ? 'Low confidence score' : null,
            'human_score' => null,
            'human_feedback' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    /**
     * Indicate that the grading has been reviewed
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'human_score' => $this->faker->randomFloat(2, 0, 10),
            'human_feedback' => $this->faker->paragraph(),
            'reviewed_by' => User::factory(),
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'flagged_for_review' => false,
        ]);
    }

    /**
     * Indicate that the grading is flagged for review
     */
    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(2, 0.3, 0.69),
            'flagged_for_review' => true,
            'review_reason' => $this->faker->randomElement([
                'Low confidence score',
                'Unusual response pattern',
                'Potential plagiarism detected',
            ]),
        ]);
    }

    /**
     * Indicate high confidence grading
     */
    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(2, 0.85, 1.0),
            'flagged_for_review' => false,
            'review_reason' => null,
        ]);
    }

    /**
     * Indicate low confidence grading
     */
    public function lowConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => $this->faker->randomFloat(2, 0.3, 0.6),
            'flagged_for_review' => true,
            'review_reason' => 'Low confidence score',
        ]);
    }

    /**
     * Generate realistic feedback based on score
     */
    protected function generateFeedback(float $score): string
    {
        $percentage = ($score / 10) * 100;

        if ($percentage >= 90) {
            $opening = "Excellent work! Your response demonstrates strong understanding.";
        } elseif ($percentage >= 75) {
            $opening = "Good work! Your response shows solid understanding with room for improvement.";
        } elseif ($percentage >= 60) {
            $opening = "Fair work. Your response addresses the question but needs more depth.";
        } else {
            $opening = "Your response needs significant improvement to meet the requirements.";
        }

        $strengths = $this->faker->randomElements([
            'Clear thesis statement',
            'Good use of examples',
            'Well-structured argument',
            'Strong conclusion',
            'Proper grammar and spelling',
        ], $this->faker->numberBetween(1, 3));

        $weaknesses = $this->faker->randomElements([
            'Could use more supporting evidence',
            'Some points lack clarity',
            'Needs better transitions',
            'Could expand on key concepts',
            'Minor grammatical issues',
        ], $this->faker->numberBetween(1, 2));

        $suggestions = $this->faker->randomElements([
            'Add more specific examples',
            'Elaborate on your main points',
            'Improve paragraph structure',
            'Proofread for clarity',
            'Strengthen your conclusion',
        ], $this->faker->numberBetween(1, 2));

        $feedback = [$opening];

        if (!empty($strengths)) {
            $feedback[] = "\n**Strengths:**";
            foreach ($strengths as $strength) {
                $feedback[] = "- " . $strength;
            }
        }

        if (!empty($weaknesses)) {
            $feedback[] = "\n**Areas for Improvement:**";
            foreach ($weaknesses as $weakness) {
                $feedback[] = "- " . $weakness;
            }
        }

        if (!empty($suggestions)) {
            $feedback[] = "\n**Suggestions:**";
            foreach ($suggestions as $suggestion) {
                $feedback[] = "- " . $suggestion;
            }
        }

        return implode("\n", $feedback);
    }

    /**
     * Generate rubric scores
     */
    protected function generateRubricScores(): array
    {
        return [
            'content' => $this->faker->randomFloat(1, 0, 10),
            'organization' => $this->faker->randomFloat(1, 0, 10),
            'grammar' => $this->faker->randomFloat(1, 0, 10),
            'critical_thinking' => $this->faker->randomFloat(1, 0, 10),
        ];
    }
}
