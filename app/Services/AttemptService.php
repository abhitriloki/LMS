<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Question;
use App\Models\User;
use App\Models\AttemptResponse;
use App\Services\AI\AIGradingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AttemptService
{
    public function __construct(
        protected QuestionService $questionService,
        protected AIGradingService $aiGradingService
    ) {}

    /**
     * Start a new assessment attempt
     */
    public function startAttempt(Assessment $assessment, User $user): AssessmentAttempt
    {
        // Check if user can attempt
        if (!$assessment->canUserAttempt($user)) {
            throw new \Exception('User has reached maximum attempts or assessment is not available');
        }

        // Create the attempt
        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return $attempt;
    }

    /**
     * Get an attempt with questions
     */
    public function getAttemptWithQuestions(AssessmentAttempt $attempt): AssessmentAttempt
    {
        // Get questions (randomized if configured)
        $questions = $this->questionService->getRandomizedQuestions(
            $attempt->assessment,
            null
        );

        // Load existing responses
        $attempt->load('responses');

        // Attach questions to attempt for easy access
        $attempt->questions = $questions;

        return $attempt;
    }

    /**
     * Save a response for a question
     */
    public function saveResponse(
        AssessmentAttempt $attempt,
        Question $question,
        $responseData
    ): AttemptResponse {
        // Check if attempt is still in progress
        if (!$attempt->isInProgress()) {
            throw new \Exception('Cannot save response for completed attempt');
        }

        // Check for time limit
        if ($attempt->isTimeLimitExceeded()) {
            $this->autoSubmitAttempt($attempt);
            throw new \Exception('Time limit exceeded, attempt has been auto-submitted');
        }

        // Find or create response
        $response = $attempt->responses()
            ->where('question_id', $question->id)
            ->first();

        if (!$response) {
            $response = new AttemptResponse([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ]);
        }

        // Save response data
        $response->response_data = $responseData;
        $response->save();

        return $response;
    }

    /**
     * Submit an attempt
     */
    public function submitAttempt(AssessmentAttempt $attempt): AssessmentAttempt
    {
        // Check if attempt is in progress
        if (!$attempt->isInProgress()) {
            throw new \Exception('Attempt is not in progress');
        }

        // Mark as submitted
        $attempt->submit();

        // Grade the attempt
        $this->gradeAttempt($attempt);

        return $attempt->fresh();
    }

    /**
     * Auto-submit an attempt (when time limit is exceeded)
     */
    public function autoSubmitAttempt(AssessmentAttempt $attempt): AssessmentAttempt
    {
        if ($attempt->isInProgress()) {
            $attempt->submit();
            $this->gradeAttempt($attempt);
        }

        return $attempt->fresh();
    }

    /**
     * Grade an attempt
     */
    public function gradeAttempt(AssessmentAttempt $attempt): AssessmentAttempt
    {
        $totalScore = 0;
        $requiresManualGrading = false;

        // Grade each response
        foreach ($attempt->responses as $response) {
            $question = $response->question;

            // Skip if already graded
            if ($response->points_earned !== null) {
                $totalScore += $response->points_earned;
                continue;
            }

            // Handle essay questions with AI grading
            if ($question->isEssay()) {
                try {
                    $rubric = $this->extractRubricFromQuestion($question);
                    $gradingResult = $this->aiGradingService->gradeEssay($response, $rubric);
                    
                    // Use AI score
                    $pointsEarned = $gradingResult->ai_score;
                    
                    $response->update([
                        'is_correct' => $pointsEarned >= ($question->points * 0.6), // 60% threshold
                        'points_earned' => $pointsEarned,
                        'feedback' => $gradingResult->ai_feedback,
                    ]);

                    // If flagged for review, mark as requiring manual grading
                    if ($gradingResult->flagged_for_review) {
                        $requiresManualGrading = true;
                        Log::info('Essay response flagged for review', [
                            'response_id' => $response->id,
                            'reason' => $gradingResult->review_reason,
                            'confidence' => $gradingResult->confidence_score,
                        ]);
                    }

                    $totalScore += $pointsEarned;
                    
                } catch (\Exception $e) {
                    Log::error('AI grading failed, marking for manual review', [
                        'response_id' => $response->id,
                        'error' => $e->getMessage(),
                    ]);
                    $requiresManualGrading = true;
                }
                continue;
            }

            // Check if question requires manual grading (fill in blank, etc.)
            if ($question->requiresManualGrading()) {
                $requiresManualGrading = true;
                continue;
            }

            // Auto-grade objective questions
            $isCorrect = $this->validateResponse($question, $response->response_data);
            $pointsEarned = $isCorrect ? $question->points : 0;

            $response->update([
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
            ]);

            $totalScore += $pointsEarned;
        }

        // Calculate percentage
        $totalPoints = $attempt->assessment->getTotalPoints();
        $percentage = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;

        // Check if passed
        $passed = $percentage >= $attempt->assessment->passing_score;

        // Update attempt
        if ($requiresManualGrading) {
            // Mark as submitted, waiting for manual grading
            $attempt->update([
                'status' => 'submitted',
                'score' => $totalScore,
                'percentage' => $percentage,
            ]);
        } else {
            // Mark as graded
            $attempt->grade($totalScore, $percentage, $passed);
        }

        return $attempt->fresh();
    }

    /**
     * Manually grade a response
     */
    public function manuallyGradeResponse(
        AttemptResponse $response,
        float $pointsEarned,
        ?string $feedback = null,
        ?User $gradedBy = null
    ): AttemptResponse {
        $question = $response->question;
        $isCorrect = $pointsEarned >= $question->points;

        $response->update([
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'feedback' => $feedback,
        ]);

        // If this response has AI grading, update it with human review
        $aiGrading = $response->aiGrading;
        if ($aiGrading && $gradedBy) {
            $aiGrading->review($gradedBy, $pointsEarned, $feedback);
        }

        // Check if all responses are graded
        $attempt = $response->attempt;
        $ungradedCount = $attempt->responses()
            ->whereNull('points_earned')
            ->count();

        if ($ungradedCount === 0) {
            // All responses graded, finalize the attempt
            $this->finalizeGrading($attempt, $gradedBy);
        }

        return $response->fresh();
    }

    /**
     * Override AI grading with instructor score
     */
    public function overrideAIGrading(
        AttemptResponse $response,
        User $instructor,
        float $humanScore,
        ?string $humanFeedback = null
    ): AttemptResponse {
        $aiGrading = $response->aiGrading;
        
        if (!$aiGrading) {
            throw new \InvalidArgumentException('No AI grading found for this response');
        }

        // Update AI grading with human review
        $aiGrading->review($instructor, $humanScore, $humanFeedback);

        // Update the response with new score
        $response->update([
            'points_earned' => $humanScore,
            'feedback' => $humanFeedback ?? $response->feedback,
            'is_correct' => $humanScore >= ($response->question->points * 0.6),
        ]);

        // Recalculate attempt score
        $attempt = $response->attempt;
        $this->recalculateAttemptScore($attempt);

        Log::info('AI grading overridden by instructor', [
            'response_id' => $response->id,
            'ai_score' => $aiGrading->ai_score,
            'human_score' => $humanScore,
            'instructor_id' => $instructor->id,
        ]);

        return $response->fresh();
    }

    /**
     * Recalculate attempt score after manual override
     */
    protected function recalculateAttemptScore(AssessmentAttempt $attempt): void
    {
        $totalScore = $attempt->responses()->sum('points_earned');
        $totalPoints = $attempt->assessment->getTotalPoints();
        $percentage = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
        $passed = $percentage >= $attempt->assessment->passing_score;

        $attempt->update([
            'score' => $totalScore,
            'percentage' => $percentage,
            'passed' => $passed,
        ]);
    }

    /**
     * Extract rubric from question metadata
     */
    protected function extractRubricFromQuestion(Question $question): array
    {
        $metadata = $question->metadata ?? [];
        return $metadata['rubric'] ?? [];
    }

    /**
     * Finalize grading after all manual grading is complete
     */
    public function finalizeGrading(AssessmentAttempt $attempt, ?User $gradedBy = null): AssessmentAttempt
    {
        $totalScore = $attempt->responses()->sum('points_earned');
        $totalPoints = $attempt->assessment->getTotalPoints();
        $percentage = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
        $passed = $percentage >= $attempt->assessment->passing_score;

        $attempt->grade($totalScore, $percentage, $passed, $gradedBy);

        return $attempt->fresh();
    }

    /**
     * Validate a response
     */
    protected function validateResponse(Question $question, $responseData): bool
    {
        if ($question->isMultipleChoice() || $question->isTrueFalse()) {
            $correctOptionIds = $question->getCorrectOptions()->pluck('id')->toArray();
            
            // Handle single selection
            if (is_numeric($responseData)) {
                return in_array($responseData, $correctOptionIds);
            }
            
            // Handle multiple selection
            if (is_array($responseData)) {
                sort($responseData);
                sort($correctOptionIds);
                return $responseData === $correctOptionIds;
            }
        }

        if ($question->isMatching()) {
            // For matching questions, response_data should be an array of pairs
            // This is a simplified validation
            return is_array($responseData) && !empty($responseData);
        }

        if ($question->isDragDrop()) {
            // For drag-drop questions, validate the order or placement
            return is_array($responseData) && !empty($responseData);
        }

        return false;
    }

    /**
     * Get user's attempts for an assessment
     */
    public function getUserAttempts(Assessment $assessment, User $user): Collection
    {
        return AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get attempt by ID
     */
    public function getAttempt(int $attemptId): ?AssessmentAttempt
    {
        return AssessmentAttempt::with([
            'assessment',
            'user',
            'responses.question.options'
        ])->find($attemptId);
    }

    /**
     * Check if user can review attempt
     */
    public function canReviewAttempt(AssessmentAttempt $attempt, User $user): bool
    {
        // User can review their own attempt if review is allowed
        if ($attempt->user_id === $user->id) {
            return $attempt->assessment->allowsReview() && $attempt->isGraded();
        }

        // Instructors and admins can always review
        return $user->isInstructor() || $user->isAdmin();
    }

    /**
     * Get attempt results
     */
    public function getAttemptResults(AssessmentAttempt $attempt): array
    {
        $assessment = $attempt->assessment;
        
        $results = [
            'attempt_id' => $attempt->id,
            'score' => $attempt->score,
            'percentage' => $attempt->percentage,
            'passed' => $attempt->passed,
            'time_taken' => $attempt->time_taken,
            'submitted_at' => $attempt->submitted_at,
            'status' => $attempt->status,
            'show_correct_answers' => $assessment->shouldShowCorrectAnswers(),
            'allow_review' => $assessment->allowsReview(),
        ];

        // Include detailed responses if review is allowed
        if ($assessment->allowsReview()) {
            $results['responses'] = $attempt->responses->map(function ($response) use ($assessment) {
                $question = $response->question;
                
                $responseData = [
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'points_earned' => $response->points_earned,
                    'is_correct' => $response->is_correct,
                    'user_response' => $response->response_data,
                    'feedback' => $response->feedback,
                ];

                // Include correct answer if configured
                if ($assessment->shouldShowCorrectAnswers()) {
                    $responseData['correct_answer'] = $question->getCorrectOptions()
                        ->pluck('id')
                        ->toArray();
                    $responseData['explanation'] = $question->explanation;
                }

                return $responseData;
            });
        }

        return $results;
    }

    /**
     * Get remaining time for an attempt
     */
    public function getRemainingTime(AssessmentAttempt $attempt): ?int
    {
        return $attempt->getRemainingTime();
    }

    /**
     * Delete an attempt
     */
    public function deleteAttempt(AssessmentAttempt $attempt): bool
    {
        // Delete all responses first
        $attempt->responses()->delete();
        
        // Delete the attempt
        return $attempt->delete();
    }

    /**
     * Get attempts requiring manual grading
     */
    public function getAttemptsRequiringGrading(Assessment $assessment): Collection
    {
        return AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('status', 'submitted')
            ->with(['user', 'responses.question'])
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    /**
     * Get attempt statistics for a user
     */
    public function getUserAttemptStatistics(Assessment $assessment, User $user): array
    {
        $attempts = $this->getUserAttempts($assessment, $user);
        $completedAttempts = $attempts->where('status', 'graded');

        if ($completedAttempts->isEmpty()) {
            return [
                'total_attempts' => $attempts->count(),
                'completed_attempts' => 0,
                'best_score' => 0,
                'average_score' => 0,
                'passed' => false,
            ];
        }

        $scores = $completedAttempts->pluck('percentage');

        return [
            'total_attempts' => $attempts->count(),
            'completed_attempts' => $completedAttempts->count(),
            'best_score' => round($scores->max(), 2),
            'average_score' => round($scores->average(), 2),
            'passed' => $completedAttempts->where('passed', true)->isNotEmpty(),
            'remaining_attempts' => $assessment->getRemainingAttempts($user),
        ];
    }
}
