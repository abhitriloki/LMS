<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;

class QuestionService
{
    /**
     * Create a new question
     */
    public function createQuestion(Assessment $assessment, array $data): Question
    {
        // Set order index if not provided
        if (!isset($data['order_index'])) {
            $data['order_index'] = $assessment->questions()->max('order_index') + 1;
        }

        // Set default points if not provided
        $data['points'] = $data['points'] ?? 1;

        $question = $assessment->questions()->create($data);

        // Create options if provided
        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $index => $optionData) {
                $this->addOption($question, array_merge($optionData, [
                    'order_index' => $optionData['order_index'] ?? $index
                ]));
            }
        }

        return $question->load('options');
    }

    /**
     * Update an existing question
     */
    public function updateQuestion(Question $question, array $data): Question
    {
        $question->update($data);

        // Update options if provided
        if (isset($data['options']) && is_array($data['options'])) {
            // Delete existing options
            $question->options()->delete();

            // Create new options
            foreach ($data['options'] as $index => $optionData) {
                $this->addOption($question, array_merge($optionData, [
                    'order_index' => $optionData['order_index'] ?? $index
                ]));
            }
        }

        return $question->fresh(['options']);
    }

    /**
     * Add an option to a question
     */
    public function addOption(Question $question, array $data): QuestionOption
    {
        // Set default values
        $data['is_correct'] = $data['is_correct'] ?? false;
        
        if (!isset($data['order_index'])) {
            $data['order_index'] = $question->options()->max('order_index') + 1;
        }

        return $question->options()->create($data);
    }

    /**
     * Update a question option
     */
    public function updateOption(QuestionOption $option, array $data): QuestionOption
    {
        $option->update($data);
        return $option->fresh();
    }

    /**
     * Delete a question option
     */
    public function deleteOption(QuestionOption $option): bool
    {
        return $option->delete();
    }

    /**
     * Delete a question
     */
    public function deleteQuestion(Question $question): bool
    {
        // Delete all options first
        $question->options()->delete();
        
        // Delete the question
        return $question->delete();
    }

    /**
     * Reorder questions in an assessment
     */
    public function reorderQuestions(Assessment $assessment, array $questionIds): void
    {
        foreach ($questionIds as $index => $questionId) {
            $assessment->questions()
                ->where('id', $questionId)
                ->update(['order_index' => $index]);
        }
    }

    /**
     * Get randomized questions for an assessment
     */
    public function getRandomizedQuestions(Assessment $assessment, ?int $count = null): Collection
    {
        $query = $assessment->questions()->with('options');

        if ($count !== null) {
            $query->inRandomOrder()->limit($count);
        } elseif ($assessment->shouldRandomizeQuestions()) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('order_index');
        }

        $questions = $query->get();

        // Randomize options if configured
        if ($assessment->shouldRandomizeOptions()) {
            $questions->each(function ($question) {
                $question->setRelation('options', $question->options->shuffle());
            });
        }

        return $questions;
    }

    /**
     * Get questions for display (ordered)
     */
    public function getQuestionsForDisplay(Assessment $assessment): Collection
    {
        return $assessment->questions()
            ->with('options')
            ->orderBy('order_index')
            ->get();
    }

    /**
     * Validate a response for a question
     */
    public function validateResponse(Question $question, $response): bool
    {
        return $question->validateResponse($response);
    }

    /**
     * Calculate score for a response
     */
    public function calculateScore(Question $question, $response): float
    {
        return $question->calculateScore($response);
    }

    /**
     * Clone a question to another assessment
     */
    public function cloneQuestion(Question $question, Assessment $targetAssessment): Question
    {
        $newQuestion = $targetAssessment->questions()->create([
            'question_text' => $question->question_text,
            'question_type' => $question->question_type,
            'points' => $question->points,
            'order_index' => $targetAssessment->questions()->max('order_index') + 1,
            'explanation' => $question->explanation,
            'metadata' => $question->metadata,
        ]);

        // Clone options
        foreach ($question->options as $option) {
            $newQuestion->options()->create([
                'option_text' => $option->option_text,
                'is_correct' => $option->is_correct,
                'order_index' => $option->order_index,
                'explanation' => $option->explanation,
            ]);
        }

        return $newQuestion->load('options');
    }

    /**
     * Bulk import questions from array
     */
    public function bulkImportQuestions(Assessment $assessment, array $questions): Collection
    {
        $createdQuestions = collect();

        foreach ($questions as $questionData) {
            $createdQuestions->push($this->createQuestion($assessment, $questionData));
        }

        return $createdQuestions;
    }

    /**
     * Get question bank (all questions from a course)
     */
    public function getQuestionBank(int $courseId): Collection
    {
        return Question::whereHas('assessment', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })
        ->with(['assessment', 'options'])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Get question statistics
     */
    public function getQuestionStatistics(Question $question): array
    {
        $responses = $question->responses()->whereHas('attempt', function ($query) {
            $query->where('status', 'graded');
        })->get();

        if ($responses->isEmpty()) {
            return [
                'total_responses' => 0,
                'correct_responses' => 0,
                'accuracy_rate' => 0,
                'average_score' => 0,
            ];
        }

        $correctCount = $responses->where('is_correct', true)->count();

        return [
            'total_responses' => $responses->count(),
            'correct_responses' => $correctCount,
            'accuracy_rate' => round(($correctCount / $responses->count()) * 100, 2),
            'average_score' => round($responses->avg('points_earned'), 2),
        ];
    }
}
