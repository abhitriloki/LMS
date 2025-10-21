<?php

namespace Database\Factories;

use App\Models\GeneratedQuestion;
use App\Models\AIQuestionJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeneratedQuestionFactory extends Factory
{
    protected $model = GeneratedQuestion::class;

    public function definition(): array
    {
        $questionType = $this->faker->randomElement(['multiple_choice', 'true_false', 'fill_in_blank', 'essay']);

        $options = null;
        $correctAnswer = null;

        if ($questionType === 'multiple_choice') {
            $options = [
                $this->faker->sentence(3),
                $this->faker->sentence(3),
                $this->faker->sentence(3),
                $this->faker->sentence(3),
            ];
            $correctAnswer = [$this->faker->numberBetween(0, 3)];
        } elseif ($questionType === 'true_false') {
            $options = ['True', 'False'];
            $correctAnswer = [$this->faker->numberBetween(0, 1)];
        } elseif ($questionType === 'fill_in_blank') {
            $correctAnswer = [$this->faker->word()];
        }

        return [
            'job_id' => AIQuestionJob::factory(),
            'question_text' => $this->faker->sentence() . '?',
            'question_type' => $questionType,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'explanation' => $this->faker->sentence(),
            'points' => $this->faker->randomFloat(1, 0.5, 5),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
            'question_id' => null,
        ];
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'multiple_choice',
            'options' => [
                $this->faker->sentence(3),
                $this->faker->sentence(3),
                $this->faker->sentence(3),
                $this->faker->sentence(3),
            ],
            'correct_answer' => [0],
        ]);
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'true_false',
            'options' => ['True', 'False'],
            'correct_answer' => [0],
        ]);
    }

    public function fillInBlank(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'fill_in_blank',
            'options' => null,
            'correct_answer' => [$this->faker->word()],
        ]);
    }

    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'essay',
            'options' => null,
            'correct_answer' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'review_notes' => 'Approved',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'review_notes' => 'Rejected',
        ]);
    }
}
