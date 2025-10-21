<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AssessmentService
{
    public function __construct(
        protected AssessmentRepositoryInterface $assessmentRepository
    ) {}

    /**
     * Create a new assessment
     */
    public function createAssessment(array $data, User $creator): Assessment
    {
        $data['created_by'] = $creator->id;
        
        // Set default values if not provided
        $data['is_published'] = $data['is_published'] ?? false;
        $data['passing_score'] = $data['passing_score'] ?? 70;
        $data['time_limit'] = $data['time_limit'] ?? 0;
        $data['max_attempts'] = $data['max_attempts'] ?? 0;
        $data['randomize_questions'] = $data['randomize_questions'] ?? false;
        $data['randomize_options'] = $data['randomize_options'] ?? false;
        $data['show_results'] = $data['show_results'] ?? true;
        $data['show_correct_answers'] = $data['show_correct_answers'] ?? false;
        $data['allow_review'] = $data['allow_review'] ?? true;

        return $this->assessmentRepository->create($data);
    }

    /**
     * Update an existing assessment
     */
    public function updateAssessment(Assessment $assessment, array $data): Assessment
    {
        return $this->assessmentRepository->update($assessment, $data);
    }

    /**
     * Get assessment by ID
     */
    public function getAssessment(int $id): ?Assessment
    {
        return $this->assessmentRepository->find($id);
    }

    /**
     * Get assessment with questions
     */
    public function getAssessmentWithQuestions(int $id): ?Assessment
    {
        return $this->assessmentRepository->findWithQuestions($id);
    }

    /**
     * Get assessments for a course
     */
    public function getAssessmentsByCourse(Course $course): Collection
    {
        return $this->assessmentRepository->getByCourse($course);
    }

    /**
     * Get assessments created by user
     */
    public function getAssessmentsByCreator(User $user): Collection
    {
        return $this->assessmentRepository->getByCreator($user);
    }

    /**
     * Publish an assessment
     */
    public function publishAssessment(Assessment $assessment): Assessment
    {
        // Validate that assessment has questions before publishing
        if ($assessment->getTotalQuestions() === 0) {
            throw new \Exception('Cannot publish assessment without questions');
        }

        return $this->assessmentRepository->publish($assessment);
    }

    /**
     * Unpublish an assessment
     */
    public function unpublishAssessment(Assessment $assessment): Assessment
    {
        return $this->assessmentRepository->unpublish($assessment);
    }

    /**
     * Delete an assessment
     */
    public function deleteAssessment(Assessment $assessment): bool
    {
        // Check if assessment has attempts
        if ($assessment->attempts()->count() > 0) {
            throw new \Exception('Cannot delete assessment with existing attempts');
        }

        return $this->assessmentRepository->delete($assessment);
    }

    /**
     * Clone an assessment
     */
    public function cloneAssessment(Assessment $assessment, User $creator): Assessment
    {
        $newAssessment = $this->createAssessment([
            'course_id' => $assessment->course_id,
            'title' => $assessment->title . ' (Copy)',
            'description' => $assessment->description,
            'instructions' => $assessment->instructions,
            'passing_score' => $assessment->passing_score,
            'time_limit' => $assessment->time_limit,
            'max_attempts' => $assessment->max_attempts,
            'randomize_questions' => $assessment->randomize_questions,
            'randomize_options' => $assessment->randomize_options,
            'show_results' => $assessment->show_results,
            'show_correct_answers' => $assessment->show_correct_answers,
            'allow_review' => $assessment->allow_review,
            'is_published' => false,
        ], $creator);

        // Clone questions
        foreach ($assessment->questions as $question) {
            $newQuestion = $newAssessment->questions()->create([
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'points' => $question->points,
                'order_index' => $question->order_index,
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
        }

        return $newAssessment;
    }

    /**
     * Search assessments with filters
     */
    public function searchAssessments(array $filters): LengthAwarePaginator
    {
        return $this->assessmentRepository->search($filters);
    }

    /**
     * Check if user can access assessment
     */
    public function canUserAccessAssessment(Assessment $assessment, User $user): bool
    {
        // Check if assessment is published
        if (!$assessment->is_published) {
            // Only creator and admins can access unpublished assessments
            return $assessment->created_by === $user->id || $user->isAdmin();
        }

        // Check if user is enrolled in the course
        $enrollment = $user->enrollments()
            ->where('course_id', $assessment->course_id)
            ->where('status', 'active')
            ->first();

        return $enrollment !== null;
    }

    /**
     * Check if user can attempt assessment
     */
    public function canUserAttemptAssessment(Assessment $assessment, User $user): bool
    {
        // Check if user can access the assessment
        if (!$this->canUserAccessAssessment($assessment, $user)) {
            return false;
        }

        // Check if user has remaining attempts
        return $assessment->canUserAttempt($user);
    }

    /**
     * Get assessment statistics
     */
    public function getAssessmentStatistics(Assessment $assessment): array
    {
        $attempts = $assessment->attempts()->completed()->get();

        if ($attempts->isEmpty()) {
            return [
                'total_attempts' => 0,
                'average_score' => 0,
                'pass_rate' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
            ];
        }

        $scores = $attempts->pluck('percentage');

        return [
            'total_attempts' => $attempts->count(),
            'average_score' => round($scores->average(), 2),
            'pass_rate' => round(($attempts->where('passed', true)->count() / $attempts->count()) * 100, 2),
            'highest_score' => round($scores->max(), 2),
            'lowest_score' => round($scores->min(), 2),
        ];
    }
}
