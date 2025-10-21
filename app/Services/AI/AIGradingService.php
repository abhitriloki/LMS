<?php

namespace App\Services\AI;

use App\Models\Question;
use App\Models\AttemptResponse;
use App\Models\AIGradingResult;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Log;

class AIGradingService
{
    protected AIServiceInterface $aiService;
    protected float $lowConfidenceThreshold = 0.7;
    protected float $plagiarismThreshold = 0.85;

    public function __construct(AIServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Grade an essay response using AI
     */
    public function gradeEssay(
        AttemptResponse $response,
        array $rubric = []
    ): AIGradingResult {
        $question = $response->question;
        $userResponse = $response->response_data;

        // Validate that this is an essay question
        if (!$question->isEssay()) {
            throw new \InvalidArgumentException('AI grading is only supported for essay questions');
        }

        try {
            // Analyze the response
            $analysis = $this->analyzeEssayResponse($userResponse, $question, $rubric);

            // Calculate scores
            $aiScore = $this->calculateScore($analysis, $question->points);
            $confidenceScore = $this->calculateConfidence($analysis);

            // Generate feedback
            $feedback = $this->generateFeedback($analysis, $userResponse);

            // Determine if flagging is needed
            $flaggedForReview = $confidenceScore < $this->lowConfidenceThreshold;
            $reviewReason = $flaggedForReview ? 'Low confidence score' : null;

            // Create grading result
            $gradingResult = AIGradingResult::create([
                'attempt_response_id' => $response->id,
                'ai_score' => $aiScore,
                'ai_feedback' => $feedback,
                'confidence_score' => $confidenceScore,
                'rubric_scores' => $analysis['rubric_scores'] ?? [],
                'flagged_for_review' => $flaggedForReview,
                'review_reason' => $reviewReason,
            ]);

            Log::info('AI grading completed', [
                'response_id' => $response->id,
                'ai_score' => $aiScore,
                'confidence' => $confidenceScore,
                'flagged' => $flaggedForReview,
            ]);

            return $gradingResult;

        } catch (AIServiceException $e) {
            Log::error('AI grading failed', [
                'response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);

            // Create a flagged result when AI fails
            return AIGradingResult::create([
                'attempt_response_id' => $response->id,
                'ai_score' => 0,
                'ai_feedback' => 'AI grading failed. Manual review required.',
                'confidence_score' => 0,
                'flagged_for_review' => true,
                'review_reason' => 'AI service error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Analyze essay response against rubric
     */
    protected function analyzeEssayResponse(
        string $response,
        Question $question,
        array $rubric = []
    ): array {
        $prompt = $this->buildGradingPrompt($response, $question, $rubric);

        try {
            $result = $this->aiService->analyzeText($prompt, 'essay_grading');

            // Ensure required fields exist
            return array_merge([
                'overall_score' => 0,
                'rubric_scores' => [],
                'strengths' => [],
                'weaknesses' => [],
                'suggestions' => [],
                'confidence' => 0.5,
            ], $result);

        } catch (\Exception $e) {
            throw new AIServiceException('Failed to analyze essay: ' . $e->getMessage());
        }
    }

    /**
     * Build grading prompt for AI
     */
    protected function buildGradingPrompt(
        string $response,
        Question $question,
        array $rubric
    ): string {
        $prompt = "You are an expert essay grader. Grade the following student response.\n\n";
        
        $prompt .= "QUESTION:\n{$question->question_text}\n\n";
        
        if ($question->explanation) {
            $prompt .= "EXPECTED ANSWER/KEY POINTS:\n{$question->explanation}\n\n";
        }

        if (!empty($rubric)) {
            $prompt .= "GRADING RUBRIC:\n";
            foreach ($rubric as $criterion => $details) {
                $prompt .= "- {$criterion}: {$details['description']} (Max: {$details['points']} points)\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "STUDENT RESPONSE:\n{$response}\n\n";

        $prompt .= "Provide your grading analysis as JSON with the following structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"overall_score\": <0-100 percentage score>,\n";
        $prompt .= "  \"rubric_scores\": {<criterion>: <score>, ...},\n";
        $prompt .= "  \"strengths\": [<list of strengths>],\n";
        $prompt .= "  \"weaknesses\": [<list of weaknesses>],\n";
        $prompt .= "  \"suggestions\": [<list of improvement suggestions>],\n";
        $prompt .= "  \"confidence\": <0-1 confidence score in your grading>\n";
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * Calculate final score from analysis
     */
    protected function calculateScore(array $analysis, float $maxPoints): float
    {
        $percentage = $analysis['overall_score'] ?? 0;
        $score = ($percentage / 100) * $maxPoints;
        
        // Round to 2 decimal places
        return round($score, 2);
    }

    /**
     * Calculate confidence score
     */
    public function calculateConfidence(array $analysis): float
    {
        $confidence = $analysis['confidence'] ?? 0.5;
        
        // Adjust confidence based on response quality indicators
        $adjustments = 0;
        
        // Lower confidence if there are many weaknesses
        if (isset($analysis['weaknesses']) && count($analysis['weaknesses']) > 3) {
            $adjustments -= 0.1;
        }
        
        // Higher confidence if there are clear strengths
        if (isset($analysis['strengths']) && count($analysis['strengths']) > 2) {
            $adjustments += 0.05;
        }
        
        $finalConfidence = $confidence + $adjustments;
        
        // Clamp between 0 and 1
        return max(0, min(1, round($finalConfidence, 2)));
    }

    /**
     * Generate detailed feedback
     */
    public function generateFeedback(array $analysis, string $response): string
    {
        $feedback = [];

        // Overall assessment
        $score = $analysis['overall_score'] ?? 0;
        if ($score >= 90) {
            $feedback[] = "Excellent work! Your response demonstrates strong understanding.";
        } elseif ($score >= 75) {
            $feedback[] = "Good work! Your response shows solid understanding with room for improvement.";
        } elseif ($score >= 60) {
            $feedback[] = "Fair work. Your response addresses the question but needs more depth.";
        } else {
            $feedback[] = "Your response needs significant improvement to meet the requirements.";
        }

        // Strengths
        if (!empty($analysis['strengths'])) {
            $feedback[] = "\n**Strengths:**";
            foreach ($analysis['strengths'] as $strength) {
                $feedback[] = "- " . $strength;
            }
        }

        // Weaknesses
        if (!empty($analysis['weaknesses'])) {
            $feedback[] = "\n**Areas for Improvement:**";
            foreach ($analysis['weaknesses'] as $weakness) {
                $feedback[] = "- " . $weakness;
            }
        }

        // Suggestions
        if (!empty($analysis['suggestions'])) {
            $feedback[] = "\n**Suggestions:**";
            foreach ($analysis['suggestions'] as $suggestion) {
                $feedback[] = "- " . $suggestion;
            }
        }

        return implode("\n", $feedback);
    }

    /**
     * Check for potential plagiarism
     */
    public function checkPlagiarism(string $response, array $otherResponses): array
    {
        $similarities = [];

        foreach ($otherResponses as $otherResponse) {
            $similarity = $this->calculateSimilarity($response, $otherResponse['text']);
            
            if ($similarity >= $this->plagiarismThreshold) {
                $similarities[] = [
                    'response_id' => $otherResponse['id'],
                    'similarity' => $similarity,
                    'user_id' => $otherResponse['user_id'],
                ];
            }
        }

        return $similarities;
    }

    /**
     * Calculate similarity between two texts
     */
    protected function calculateSimilarity(string $text1, string $text2): float
    {
        // Simple similarity calculation using Levenshtein distance
        // In production, consider using more sophisticated algorithms
        
        $text1 = strtolower(trim($text1));
        $text2 = strtolower(trim($text2));

        if ($text1 === $text2) {
            return 1.0;
        }

        $maxLength = max(strlen($text1), strlen($text2));
        if ($maxLength === 0) {
            return 0.0;
        }

        $distance = levenshtein(
            substr($text1, 0, 255),
            substr($text2, 0, 255)
        );

        $similarity = 1 - ($distance / min(255, $maxLength));
        
        return round($similarity, 2);
    }

    /**
     * Batch grade multiple essay responses
     */
    public function batchGradeEssays(array $responses, array $rubric = []): array
    {
        $results = [];

        foreach ($responses as $response) {
            try {
                $results[] = $this->gradeEssay($response, $rubric);
            } catch (\Exception $e) {
                Log::error('Batch grading failed for response', [
                    'response_id' => $response->id,
                    'error' => $e->getMessage(),
                ]);
                
                $results[] = null;
            }
        }

        return $results;
    }

    /**
     * Get responses flagged for review
     */
    public function getFlaggedResponses(int $assessmentId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = AIGradingResult::where('flagged_for_review', true)
            ->whereNull('reviewed_at')
            ->with(['attemptResponse.question', 'attemptResponse.attempt.user']);

        if ($assessmentId) {
            $query->whereHas('attemptResponse.attempt', function ($q) use ($assessmentId) {
                $q->where('assessment_id', $assessmentId);
            });
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Set low confidence threshold
     */
    public function setLowConfidenceThreshold(float $threshold): void
    {
        $this->lowConfidenceThreshold = max(0, min(1, $threshold));
    }

    /**
     * Set plagiarism threshold
     */
    public function setPlagiarismThreshold(float $threshold): void
    {
        $this->plagiarismThreshold = max(0, min(1, $threshold));
    }
}
