<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\Course;
use App\Models\AIRecommendation;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIRecommendationService
{
    protected AIServiceInterface $aiService;

    public function __construct(AIServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate personalized course recommendations for a user
     */
    public function generateRecommendations(User $user, int $limit = 5): array
    {
        try {
            // Analyze user profile and learning history
            $userProfile = $this->analyzeUserProfile($user);
            $learningHistory = $this->analyzeLearningHistory($user);
            $skillGaps = $this->identifySkillGaps($user);

            // Build AI prompt for recommendations
            $prompt = $this->buildRecommendationPrompt($user, $userProfile, $learningHistory, $skillGaps);

            // Get AI recommendations
            $aiResponse = $this->aiService->generateText($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            // Parse AI response
            $recommendations = $this->parseRecommendations($aiResponse, $user);

            // Store recommendations in database
            $this->storeRecommendations($user, $recommendations, $limit);

            return array_slice($recommendations, 0, $limit);

        } catch (\Exception $e) {
            Log::error('Failed to generate recommendations', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            throw new AIServiceException('Failed to generate recommendations: ' . $e->getMessage());
        }
    }

    /**
     * Analyze user profile
     */
    protected function analyzeUserProfile(User $user): array
    {
        return [
            'role' => $user->role,
            'position' => $user->position,
            'department' => $user->department?->name,
            'bio' => $user->bio,
            'preferences' => $user->preferences ?? [],
        ];
    }

    /**
     * Analyze user's learning history
     */
    protected function analyzeLearningHistory(User $user): array
    {
        $completedCourses = $user->enrollments()
            ->where('status', 'completed')
            ->with('course.category')
            ->get();

        $inProgressCourses = $user->enrollments()
            ->where('status', 'active')
            ->with('course.category')
            ->get();

        $categories = $completedCourses->pluck('course.category.name')->unique()->values()->toArray();
        $difficultyLevels = $completedCourses->pluck('course.difficulty_level')->unique()->values()->toArray();

        $averageScore = $user->assessmentAttempts()
            ->where('status', 'completed')
            ->avg('score') ?? 0;

        return [
            'completed_courses_count' => $completedCourses->count(),
            'in_progress_courses_count' => $inProgressCourses->count(),
            'completed_courses' => $completedCourses->pluck('course.title')->toArray(),
            'categories_completed' => $categories,
            'difficulty_levels_completed' => $difficultyLevels,
            'average_assessment_score' => round($averageScore, 2),
            'recent_activity' => $this->getRecentActivity($user),
        ];
    }

    /**
     * Get recent learning activity
     */
    protected function getRecentActivity(User $user): array
    {
        $recentEnrollments = $user->enrollments()
            ->with('course')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $recentEnrollments->map(function ($enrollment) {
            return [
                'course' => $enrollment->course->title,
                'status' => $enrollment->status,
                'progress' => $enrollment->progress_percentage,
                'enrolled_at' => $enrollment->created_at->format('Y-m-d'),
            ];
        })->toArray();
    }

    /**
     * Identify skill gaps based on user's role and completed courses
     */
    protected function identifySkillGaps(User $user): array
    {
        // Get courses relevant to user's role and department
        $relevantCourses = Course::query()
            ->published()
            ->when($user->department_id, function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->whereJsonContains('target_audience', $user->department->name)
                      ->orWhereNull('target_audience');
                });
            })
            ->get();

        // Get courses user hasn't taken
        $enrolledCourseIds = $user->enrollments()->pluck('course_id')->toArray();
        $notTakenCourses = $relevantCourses->whereNotIn('id', $enrolledCourseIds);

        // Identify skill gaps
        $skillGaps = [];
        $completedCategories = $user->enrollments()
            ->where('status', 'completed')
            ->with('course.category')
            ->get()
            ->pluck('course.category.name')
            ->unique()
            ->toArray();

        $availableCategories = $notTakenCourses->pluck('category.name')->unique()->toArray();
        $missingCategories = array_diff($availableCategories, $completedCategories);

        return [
            'missing_categories' => array_values($missingCategories),
            'not_taken_courses_count' => $notTakenCourses->count(),
            'suggested_difficulty' => $this->suggestNextDifficulty($user),
        ];
    }

    /**
     * Suggest next difficulty level based on user's progress
     */
    protected function suggestNextDifficulty(User $user): string
    {
        $completedCourses = $user->enrollments()
            ->where('status', 'completed')
            ->with('course')
            ->get();

        if ($completedCourses->isEmpty()) {
            return 'beginner';
        }

        $averageScore = $user->assessmentAttempts()
            ->where('status', 'completed')
            ->avg('score') ?? 0;

        $hasAdvanced = $completedCourses->where('course.difficulty_level', 'advanced')->isNotEmpty();
        $hasIntermediate = $completedCourses->where('course.difficulty_level', 'intermediate')->isNotEmpty();

        if ($hasAdvanced && $averageScore >= 80) {
            return 'advanced';
        } elseif ($hasIntermediate && $averageScore >= 75) {
            return 'advanced';
        } elseif ($averageScore >= 70) {
            return 'intermediate';
        }

        return 'beginner';
    }

    /**
     * Build AI prompt for recommendations
     */
    protected function buildRecommendationPrompt(
        User $user,
        array $userProfile,
        array $learningHistory,
        array $skillGaps
    ): string {
        $prompt = "You are an expert learning advisor for a corporate LMS. Generate personalized course recommendations.\n\n";
        
        $prompt .= "USER PROFILE:\n";
        $prompt .= "- Role: {$userProfile['role']}\n";
        $prompt .= "- Position: {$userProfile['position']}\n";
        $prompt .= "- Department: {$userProfile['department']}\n";
        
        $prompt .= "\nLEARNING HISTORY:\n";
        $prompt .= "- Completed Courses: {$learningHistory['completed_courses_count']}\n";
        $prompt .= "- In Progress: {$learningHistory['in_progress_courses_count']}\n";
        $prompt .= "- Average Assessment Score: {$learningHistory['average_assessment_score']}%\n";
        $prompt .= "- Categories Completed: " . implode(', ', $learningHistory['categories_completed']) . "\n";
        
        if (!empty($learningHistory['completed_courses'])) {
            $prompt .= "- Recent Completed Courses: " . implode(', ', array_slice($learningHistory['completed_courses'], 0, 5)) . "\n";
        }
        
        $prompt .= "\nSKILL GAPS:\n";
        $prompt .= "- Missing Categories: " . implode(', ', $skillGaps['missing_categories']) . "\n";
        $prompt .= "- Suggested Difficulty: {$skillGaps['suggested_difficulty']}\n";
        
        // Get available courses
        $availableCourses = $this->getAvailableCourses($user);
        $prompt .= "\nAVAILABLE COURSES:\n";
        foreach ($availableCourses as $course) {
            $prompt .= "- ID: {$course->id}, Title: {$course->title}, Category: {$course->category->name}, ";
            $prompt .= "Difficulty: {$course->difficulty_level}, Duration: {$course->estimated_duration} hours\n";
        }
        
        $prompt .= "\nTASK:\n";
        $prompt .= "Based on the user's profile, learning history, and skill gaps, recommend 5-10 courses from the available courses list.\n";
        $prompt .= "For each recommendation, provide:\n";
        $prompt .= "1. course_id (from the available courses list)\n";
        $prompt .= "2. score (0.0 to 1.0 indicating relevance)\n";
        $prompt .= "3. reasoning (2-3 sentences explaining why this course is recommended)\n\n";
        $prompt .= "Return ONLY a valid JSON array with this structure:\n";
        $prompt .= '{"recommendations": [{"course_id": 1, "score": 0.95, "reasoning": "..."}, ...]}';

        return $prompt;
    }

    /**
     * Get available courses for user
     */
    protected function getAvailableCourses(User $user): Collection
    {
        $enrolledCourseIds = $user->enrollments()->pluck('course_id')->toArray();

        return Course::query()
            ->published()
            ->with('category')
            ->whereNotIn('id', $enrolledCourseIds)
            ->limit(50)
            ->get();
    }

    /**
     * Parse AI recommendations response
     */
    protected function parseRecommendations(string $aiResponse, User $user): array
    {
        try {
            // Try to extract JSON from response
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['recommendations'])) {
                    return $data['recommendations'];
                }
            }

            Log::warning('Failed to parse AI recommendations response', [
                'user_id' => $user->id,
                'response' => $aiResponse
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('Error parsing recommendations', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Store recommendations in database
     */
    protected function storeRecommendations(User $user, array $recommendations, int $limit): void
    {
        // Expire old active recommendations
        AIRecommendation::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        // Store new recommendations
        $count = 0;
        foreach ($recommendations as $rec) {
            if ($count >= $limit) {
                break;
            }

            if (!isset($rec['course_id']) || !isset($rec['score']) || !isset($rec['reasoning'])) {
                continue;
            }

            // Verify course exists
            $course = Course::find($rec['course_id']);
            if (!$course) {
                continue;
            }

            AIRecommendation::create([
                'user_id' => $user->id,
                'course_id' => $rec['course_id'],
                'recommendation_type' => 'ai_generated',
                'score' => $rec['score'],
                'reasoning' => $rec['reasoning'],
                'status' => 'active',
                'expires_at' => now()->addDays(30),
            ]);

            $count++;
        }
    }

    /**
     * Get personalized courses for user
     */
    public function getPersonalizedCourses(User $user, int $limit = 10): Collection
    {
        // Get active recommendations
        $recommendations = AIRecommendation::where('user_id', $user->id)
            ->active()
            ->with('course.category')
            ->orderBy('score', 'desc')
            ->limit($limit)
            ->get();

        return $recommendations->pluck('course');
    }

    /**
     * Record feedback on recommendation
     */
    public function recordFeedback(AIRecommendation $recommendation, string $action, string $feedback = null): void
    {
        if ($action === 'accept') {
            $recommendation->accept($feedback);
        } elseif ($action === 'reject') {
            $recommendation->reject($feedback);
        }

        Log::info('Recommendation feedback recorded', [
            'recommendation_id' => $recommendation->id,
            'user_id' => $recommendation->user_id,
            'action' => $action,
            'feedback' => $feedback
        ]);
    }

    /**
     * Get active recommendations for user
     */
    public function getActiveRecommendations(User $user): Collection
    {
        return AIRecommendation::where('user_id', $user->id)
            ->active()
            ->with('course.category')
            ->orderBy('score', 'desc')
            ->get();
    }

    /**
     * Check if user needs new recommendations
     */
    public function needsNewRecommendations(User $user): bool
    {
        $activeCount = AIRecommendation::where('user_id', $user->id)
            ->active()
            ->count();

        return $activeCount < 3;
    }
}
