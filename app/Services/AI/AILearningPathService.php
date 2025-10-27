<?php

namespace App\Services\AI;

use App\Models\AILearningPath;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AILearningPathService
{
    protected AIServiceInterface $aiService;

    public function __construct(AIServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate a personalized learning path for a user
     */
    public function generateLearningPath(User $user, string $targetRole, array $options = []): AILearningPath
    {
        try {
            // Analyze user's current skills and learning history
            $userAnalysis = $this->analyzeUserSkills($user);
            
            // Get available courses
            $availableCourses = $this->getAvailableCourses($user);
            
            // Build AI prompt for path generation
            $prompt = $this->buildPathGenerationPrompt($user, $targetRole, $userAnalysis, $availableCourses);
            
            // Generate path using AI
            $aiResponse = $this->aiService->generateText($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 3000,
            ]);
            
            // Parse AI response
            $pathData = $this->parseAIResponse($aiResponse);
            
            // Validate prerequisites
            $validatedPath = $this->validatePrerequisites($pathData, $availableCourses);
            
            // Calculate estimated duration
            $estimatedDuration = $this->calculateEstimatedDuration($validatedPath);
            
            // Create learning path record
            $learningPath = AILearningPath::create([
                'user_id' => $user->id,
                'target_role' => $targetRole,
                'current_skills' => $userAnalysis['current_skills'],
                'target_skills' => $userAnalysis['target_skills'],
                'path_data' => $validatedPath,
                'estimated_duration' => $estimatedDuration,
                'status' => 'draft',
                'progress_percentage' => 0,
            ]);
            
            Log::info('Learning path generated', [
                'user_id' => $user->id,
                'target_role' => $targetRole,
                'path_id' => $learningPath->id,
                'course_count' => count($validatedPath['courses'] ?? [])
            ]);
            
            return $learningPath;
            
        } catch (\Exception $e) {
            Log::error('Learning path generation failed', [
                'user_id' => $user->id,
                'target_role' => $targetRole,
                'error' => $e->getMessage()
            ]);
            
            throw new AIServiceException('Failed to generate learning path: ' . $e->getMessage());
        }
    }

    /**
     * Analyze user's current skills and learning history
     */
    protected function analyzeUserSkills(User $user): array
    {
        // Get completed courses
        $completedCourses = $user->enrollments()
            ->where('status', 'completed')
            ->with('course')
            ->get();
        
        // Get in-progress courses
        $inProgressCourses = $user->enrollments()
            ->where('status', 'active')
            ->with('course')
            ->get();
        
        // Extract skills from completed courses
        $currentSkills = [];
        foreach ($completedCourses as $enrollment) {
            if ($enrollment->course && $enrollment->course->tags) {
                $currentSkills = array_merge($currentSkills, $enrollment->course->tags);
            }
        }
        $currentSkills = array_unique($currentSkills);
        
        // Get assessment scores
        $assessmentScores = DB::table('assessment_attempts')
            ->join('assessments', 'assessment_attempts.assessment_id', '=', 'assessments.id')
            ->where('assessment_attempts.user_id', $user->id)
            ->where('assessment_attempts.status', 'completed')
            ->select('assessments.course_id', DB::raw('AVG(assessment_attempts.score) as avg_score'))
            ->groupBy('assessments.course_id')
            ->get();
        
        // Determine target skills based on role and department
        $targetSkills = $this->determineTargetSkills($user);
        
        return [
            'current_skills' => $currentSkills,
            'target_skills' => $targetSkills,
            'completed_courses' => $completedCourses->pluck('course.title')->toArray(),
            'in_progress_courses' => $inProgressCourses->pluck('course.title')->toArray(),
            'assessment_performance' => $assessmentScores->toArray(),
            'skill_gaps' => array_diff($targetSkills, $currentSkills),
        ];
    }

    /**
     * Determine target skills based on user's role and department
     */
    protected function determineTargetSkills(User $user): array
    {
        // This could be enhanced with a skills database
        $roleSkills = [
            'Senior Developer' => ['Advanced PHP', 'System Architecture', 'Team Leadership', 'Code Review'],
            'Project Manager' => ['Project Planning', 'Agile Methodologies', 'Risk Management', 'Stakeholder Communication'],
            'Data Analyst' => ['SQL', 'Data Visualization', 'Statistical Analysis', 'Python'],
            'DevOps Engineer' => ['CI/CD', 'Docker', 'Kubernetes', 'Cloud Infrastructure'],
        ];
        
        return $roleSkills[$user->position] ?? [];
    }

    /**
     * Get available courses for the user
     */
    protected function getAvailableCourses(User $user): Collection
    {
        return Course::where('is_published', true)
            ->with(['category', 'modules'])
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->short_description ?? $course->description,
                    'category' => $course->category->name ?? 'Uncategorized',
                    'difficulty_level' => $course->difficulty_level,
                    'estimated_duration' => $course->estimated_duration,
                    'prerequisites' => $course->prerequisites ?? [],
                    'tags' => $course->tags ?? [],
                    'learning_objectives' => $course->learning_objectives ?? [],
                ];
            });
    }

    /**
     * Build AI prompt for learning path generation
     */
    protected function buildPathGenerationPrompt(User $user, string $targetRole, array $userAnalysis, Collection $courses): string
    {
        $coursesJson = json_encode($courses->toArray(), JSON_PRETTY_PRINT);
        $departmentName = $user->department ? $user->department->name : 'N/A';
        
        return <<<PROMPT
You are an expert learning path optimizer for corporate training. Generate a personalized learning path for an employee.

**User Profile:**
- Name: {$user->name}
- Current Role: {$user->position}
- Target Role: {$targetRole}
- Department: {$departmentName}

**Current Skills:**
{$this->formatSkillsList($userAnalysis['current_skills'])}

**Target Skills:**
{$this->formatSkillsList($userAnalysis['target_skills'])}

**Skill Gaps:**
{$this->formatSkillsList($userAnalysis['skill_gaps'])}

**Completed Courses:**
{$this->formatCoursesList($userAnalysis['completed_courses'])}

**In Progress Courses:**
{$this->formatCoursesList($userAnalysis['in_progress_courses'])}

**Available Courses:**
{$coursesJson}

**Task:**
Create an optimal learning path that:
1. Addresses the skill gaps identified
2. Follows a logical progression from beginner to advanced
3. Respects course prerequisites
4. Balances difficulty levels appropriately
5. Includes milestones for motivation
6. Provides estimated timeline

**Output Format (JSON):**
{
  "reasoning": "Brief explanation of the path design",
  "courses": [
    {
      "course_id": 1,
      "order": 1,
      "milestone": "Foundation",
      "reason": "Why this course is included",
      "estimated_weeks": 2
    }
  ],
  "milestones": [
    {
      "name": "Foundation Complete",
      "course_ids": [1, 2],
      "description": "Basic skills acquired"
    }
  ],
  "total_estimated_weeks": 12,
  "difficulty_progression": "beginner -> intermediate -> advanced"
}

Return ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Format skills list for prompt
     */
    protected function formatSkillsList(array $skills): string
    {
        if (empty($skills)) {
            return '- None';
        }
        
        return '- ' . implode("\n- ", $skills);
    }

    /**
     * Format courses list for prompt
     */
    protected function formatCoursesList(array $courses): string
    {
        if (empty($courses)) {
            return '- None';
        }
        
        return '- ' . implode("\n- ", $courses);
    }

    /**
     * Parse AI response into structured data
     */
    protected function parseAIResponse(string $response): array
    {
        // Try to extract JSON from response
        $response = trim($response);
        
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AIServiceException('Failed to parse AI response: ' . json_last_error_msg());
        }
        
        // Validate required fields
        if (!isset($data['courses']) || !is_array($data['courses'])) {
            throw new AIServiceException('Invalid AI response: missing courses array');
        }
        
        return $data;
    }

    /**
     * Validate and ensure proper prerequisite ordering
     */
    protected function validatePrerequisites(array $pathData, Collection $availableCourses): array
    {
        $courses = $pathData['courses'] ?? [];
        $courseMap = $availableCourses->keyBy('id');
        
        // Sort courses by order
        usort($courses, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });
        
        $validatedCourses = [];
        $completedCourseIds = [];
        
        foreach ($courses as $courseItem) {
            $courseId = $courseItem['course_id'];
            $course = $courseMap->get($courseId);
            
            if (!$course) {
                Log::warning('Course not found in learning path', ['course_id' => $courseId]);
                continue;
            }
            
            // Check prerequisites
            $prerequisites = $course['prerequisites'] ?? [];
            $prerequisitesMet = true;
            
            foreach ($prerequisites as $prereqId) {
                if (!in_array($prereqId, $completedCourseIds)) {
                    $prerequisitesMet = false;
                    Log::warning('Prerequisite not met in learning path', [
                        'course_id' => $courseId,
                        'prerequisite_id' => $prereqId
                    ]);
                }
            }
            
            if ($prerequisitesMet) {
                $validatedCourses[] = $courseItem;
                $completedCourseIds[] = $courseId;
            }
        }
        
        $pathData['courses'] = $validatedCourses;
        
        return $pathData;
    }

    /**
     * Calculate total estimated duration
     */
    protected function calculateEstimatedDuration(array $pathData): int
    {
        $totalWeeks = 0;
        
        foreach ($pathData['courses'] ?? [] as $course) {
            $totalWeeks += $course['estimated_weeks'] ?? 1;
        }
        
        return $totalWeeks;
    }

    /**
     * Optimize an existing learning path
     */
    public function optimizePath(AILearningPath $learningPath): AILearningPath
    {
        try {
            $user = $learningPath->user;
            
            // Re-analyze user's current state
            $userAnalysis = $this->analyzeUserSkills($user);
            
            // Get current path data
            $currentPath = $learningPath->path_data;
            
            // Build optimization prompt
            $prompt = $this->buildOptimizationPrompt($learningPath, $userAnalysis, $currentPath);
            
            // Get AI suggestions
            $aiResponse = $this->aiService->generateText($prompt, [
                'temperature' => 0.5,
                'max_tokens' => 2000,
            ]);
            
            // Parse and apply optimizations
            $optimizedPath = $this->parseAIResponse($aiResponse);
            
            // Validate the optimized path
            $availableCourses = $this->getAvailableCourses($user);
            $validatedPath = $this->validatePrerequisites($optimizedPath, $availableCourses);
            
            // Update learning path
            $learningPath->update([
                'path_data' => $validatedPath,
                'estimated_duration' => $this->calculateEstimatedDuration($validatedPath),
                'last_adjusted_at' => now(),
            ]);
            
            Log::info('Learning path optimized', [
                'path_id' => $learningPath->id,
                'user_id' => $user->id
            ]);
            
            return $learningPath->fresh();
            
        } catch (\Exception $e) {
            Log::error('Learning path optimization failed', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            throw new AIServiceException('Failed to optimize learning path: ' . $e->getMessage());
        }
    }

    /**
     * Build optimization prompt
     */
    protected function buildOptimizationPrompt(AILearningPath $learningPath, array $userAnalysis, array $currentPath): string
    {
        $currentPathJson = json_encode($currentPath, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
You are optimizing an existing learning path based on updated user progress.

**Current Learning Path:**
{$currentPathJson}

**Updated User Skills:**
{$this->formatSkillsList($userAnalysis['current_skills'])}

**Recently Completed Courses:**
{$this->formatCoursesList($userAnalysis['completed_courses'])}

**Current Progress:** {$learningPath->progress_percentage}%

**Task:**
Optimize the learning path by:
1. Removing courses that are no longer needed
2. Adjusting difficulty progression based on performance
3. Adding new courses if skill gaps are identified
4. Reordering courses for better learning flow

Return the optimized path in the same JSON format as the original.
PROMPT;
    }

    /**
     * Adjust learning path based on user performance
     */
    public function adjustPath(AILearningPath $learningPath, array $performanceData): AILearningPath
    {
        try {
            $currentPath = $learningPath->path_data;
            $user = $learningPath->user;
            
            // Analyze performance
            $adjustmentReason = $this->analyzePerformance($performanceData);
            
            // Build adjustment prompt
            $prompt = $this->buildAdjustmentPrompt($learningPath, $performanceData, $adjustmentReason);
            
            // Get AI suggestions
            $aiResponse = $this->aiService->generateText($prompt, [
                'temperature' => 0.6,
                'max_tokens' => 2000,
            ]);
            
            // Parse adjustments
            $adjustedPath = $this->parseAIResponse($aiResponse);
            
            // Validate adjustments
            $availableCourses = $this->getAvailableCourses($user);
            $validatedPath = $this->validatePrerequisites($adjustedPath, $availableCourses);
            
            // Apply adjustments
            $learningPath->adjust($validatedPath, $adjustmentReason);
            
            Log::info('Learning path adjusted', [
                'path_id' => $learningPath->id,
                'reason' => $adjustmentReason
            ]);
            
            return $learningPath->fresh();
            
        } catch (\Exception $e) {
            Log::error('Learning path adjustment failed', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            throw new AIServiceException('Failed to adjust learning path: ' . $e->getMessage());
        }
    }

    /**
     * Analyze performance data to determine adjustment needs
     */
    protected function analyzePerformance(array $performanceData): string
    {
        $avgScore = $performanceData['average_score'] ?? 0;
        $completionRate = $performanceData['completion_rate'] ?? 0;
        $timeSpent = $performanceData['time_spent'] ?? 0;
        
        if ($avgScore >= 90 && $completionRate >= 90) {
            return 'Excellent performance - accelerate learning path';
        } elseif ($avgScore < 60 || $completionRate < 50) {
            return 'Struggling with current difficulty - add foundational courses';
        } elseif ($timeSpent > ($performanceData['estimated_time'] ?? 0) * 1.5) {
            return 'Taking longer than expected - adjust pace and difficulty';
        }
        
        return 'Normal progress - minor optimizations';
    }

    /**
     * Build adjustment prompt
     */
    protected function buildAdjustmentPrompt(AILearningPath $learningPath, array $performanceData, string $reason): string
    {
        $currentPathJson = json_encode($learningPath->path_data, JSON_PRETTY_PRINT);
        $performanceJson = json_encode($performanceData, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Adjust the learning path based on user performance.

**Current Path:**
{$currentPathJson}

**Performance Data:**
{$performanceJson}

**Adjustment Reason:** {$reason}

**Task:**
Modify the learning path to address the performance issues or opportunities.
Consider:
1. Adding remedial courses if struggling
2. Skipping redundant courses if excelling
3. Adjusting difficulty progression
4. Reordering courses for better flow

Return the adjusted path in JSON format.
PROMPT;
    }

    /**
     * Update progress for a learning path
     */
    public function updateProgress(AILearningPath $learningPath): void
    {
        $user = $learningPath->user;
        $pathCourses = collect($learningPath->path_data['courses'] ?? []);
        
        if ($pathCourses->isEmpty()) {
            return;
        }
        
        // Get completed courses from the path
        $completedCount = 0;
        foreach ($pathCourses as $pathCourse) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $pathCourse['course_id'])
                ->where('status', 'completed')
                ->first();
            
            if ($enrollment) {
                $completedCount++;
            }
        }
        
        // Calculate progress percentage
        $progressPercentage = ($completedCount / $pathCourses->count()) * 100;
        
        // Update learning path
        $learningPath->updateProgress($progressPercentage);
        
        Log::info('Learning path progress updated', [
            'path_id' => $learningPath->id,
            'progress' => $progressPercentage,
            'completed' => $completedCount,
            'total' => $pathCourses->count()
        ]);
    }

    /**
     * Get next course in the learning path
     */
    public function getNextCourse(AILearningPath $learningPath): ?array
    {
        $user = $learningPath->user;
        $pathCourses = collect($learningPath->path_data['courses'] ?? []);
        
        foreach ($pathCourses as $pathCourse) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $pathCourse['course_id'])
                ->first();
            
            // If not enrolled or not completed, this is the next course
            if (!$enrollment || $enrollment->status !== 'completed') {
                return $pathCourse;
            }
        }
        
        return null; // All courses completed
    }
}
