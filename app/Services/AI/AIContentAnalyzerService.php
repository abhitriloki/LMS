<?php

namespace App\Services\AI;

use App\Models\Course;
use App\Models\ContentAnalysis;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIContentAnalyzerService
{
    protected AIServiceInterface $aiService;

    public function __construct(AIServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Analyze course content for readability, completeness, and quality
     */
    public function analyzeCourse(Course $course): ContentAnalysis
    {
        try {
            // Extract content from course
            $content = $this->extractCourseContent($course);

            // Perform readability analysis
            $readabilityScore = $this->analyzeReadability($content);

            // Identify content gaps
            $gaps = $this->identifyContentGaps($course, $content);

            // Generate suggestions
            $suggestions = $this->generateSuggestions($course, $content, $readabilityScore, $gaps);

            // Check accessibility
            $accessibilityIssues = $this->checkAccessibility($content);

            // Calculate overall score
            $overallScore = $this->calculateOverallScore($readabilityScore, $gaps, $accessibilityIssues);

            // Store analysis results
            $analysis = $this->storeAnalysis($course, [
                'readability_score' => $readabilityScore,
                'overall_score' => $overallScore,
                'content_gaps' => $gaps,
                'suggestions' => $suggestions,
                'accessibility_issues' => $accessibilityIssues,
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Content analysis failed', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            throw new AIServiceException('Failed to analyze course content: ' . $e->getMessage());
        }
    }

    /**
     * Extract text content from course modules and lessons
     */
    protected function extractCourseContent(Course $course): array
    {
        $content = [
            'title' => $course->title,
            'description' => $course->description,
            'learning_objectives' => $course->learning_objectives ?? [],
            'lessons' => [],
        ];

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                $lessonContent = [
                    'title' => $lesson->title,
                    'content_type' => $lesson->content_type,
                    'duration' => $lesson->duration,
                ];

                // Extract text content based on content type
                if ($lesson->content_type === 'text' && $lesson->content) {
                    $lessonContent['text'] = strip_tags($lesson->content);
                }

                $content['lessons'][] = $lessonContent;
            }
        }

        return $content;
    }

    /**
     * Analyze readability using AI
     */
    protected function analyzeReadability(array $content): array
    {
        $prompt = $this->buildReadabilityPrompt($content);

        $aiResponse = $this->aiService->generateText($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 1500,
        ]);

        return $this->parseReadabilityResponse($aiResponse);
    }

    /**
     * Build prompt for readability analysis
     */
    protected function buildReadabilityPrompt(array $content): string
    {
        $text = $content['description'] ?? '';
        
        foreach ($content['lessons'] as $lesson) {
            if (isset($lesson['text'])) {
                $text .= "\n\n" . $lesson['text'];
            }
        }

        return <<<PROMPT
Analyze the following course content for readability and complexity. Provide a detailed assessment.

Course Title: {$content['title']}
Course Description: {$content['description']}

Content Sample:
{$text}

Please analyze and provide:
1. Readability score (0-100, where 100 is most readable)
2. Complexity level (beginner, intermediate, advanced)
3. Average sentence length
4. Vocabulary complexity
5. Engagement level (0-100)
6. Specific readability issues found

Return your response in JSON format:
{
    "readability_score": 75,
    "complexity_level": "intermediate",
    "avg_sentence_length": 15,
    "vocabulary_complexity": "moderate",
    "engagement_score": 80,
    "issues": ["issue1", "issue2"]
}
PROMPT;
    }

    /**
     * Parse AI readability response
     */
    protected function parseReadabilityResponse(string $response): array
    {
        // Extract JSON from response
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
            if ($data) {
                return $data;
            }
        }

        // Fallback default values
        return [
            'readability_score' => 70,
            'complexity_level' => 'intermediate',
            'avg_sentence_length' => 15,
            'vocabulary_complexity' => 'moderate',
            'engagement_score' => 70,
            'issues' => [],
        ];
    }

    /**
     * Identify content gaps and missing topics
     */
    protected function identifyContentGaps(Course $course, array $content): array
    {
        $prompt = $this->buildGapAnalysisPrompt($course, $content);

        $aiResponse = $this->aiService->generateText($prompt, [
            'temperature' => 0.4,
            'max_tokens' => 1500,
        ]);

        return $this->parseGapAnalysisResponse($aiResponse);
    }

    /**
     * Build prompt for gap analysis
     */
    protected function buildGapAnalysisPrompt(Course $course, array $content): string
    {
        $objectives = implode("\n", $course->learning_objectives ?? []);
        $lessonTitles = array_map(fn($l) => $l['title'], $content['lessons']);
        $lessonList = implode("\n", $lessonTitles);

        return <<<PROMPT
Analyze this course structure for content gaps and missing topics.

Course: {$course->title}
Difficulty: {$course->difficulty_level}
Description: {$course->description}

Learning Objectives:
{$objectives}

Current Lessons:
{$lessonList}

Identify:
1. Missing topics that should be covered based on objectives
2. Gaps in the learning progression
3. Topics that need more depth
4. Prerequisites that aren't covered

Return your response in JSON format:
{
    "missing_topics": ["topic1", "topic2"],
    "progression_gaps": ["gap1", "gap2"],
    "needs_more_depth": ["topic1"],
    "missing_prerequisites": ["prereq1"]
}
PROMPT;
    }

    /**
     * Parse gap analysis response
     */
    protected function parseGapAnalysisResponse(string $response): array
    {
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
            if ($data) {
                return $data;
            }
        }

        return [
            'missing_topics' => [],
            'progression_gaps' => [],
            'needs_more_depth' => [],
            'missing_prerequisites' => [],
        ];
    }

    /**
     * Generate improvement suggestions
     */
    protected function generateSuggestions(Course $course, array $content, array $readability, array $gaps): array
    {
        $prompt = $this->buildSuggestionsPrompt($course, $content, $readability, $gaps);

        $aiResponse = $this->aiService->generateText($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 2000,
        ]);

        return $this->parseSuggestionsResponse($aiResponse);
    }

    /**
     * Build prompt for suggestions
     */
    protected function buildSuggestionsPrompt(Course $course, array $content, array $readability, array $gaps): string
    {
        $readabilityScore = $readability['readability_score'] ?? 70;
        $engagementScore = $readability['engagement_score'] ?? 70;
        $missingTopics = implode(', ', $gaps['missing_topics'] ?? []);

        return <<<PROMPT
Based on the following course analysis, provide specific, actionable suggestions for improvement.

Course: {$course->title}
Readability Score: {$readabilityScore}/100
Engagement Score: {$engagementScore}/100
Missing Topics: {$missingTopics}

Provide 5-10 specific suggestions to improve:
1. Content clarity and readability
2. Course structure and flow
3. Engagement and interactivity
4. Completeness and depth

Return your response in JSON format:
{
    "suggestions": [
        {
            "category": "readability",
            "priority": "high",
            "suggestion": "Specific suggestion text",
            "impact": "Expected impact"
        }
    ]
}
PROMPT;
    }

    /**
     * Parse suggestions response
     */
    protected function parseSuggestionsResponse(string $response): array
    {
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
            if ($data && isset($data['suggestions'])) {
                return $data['suggestions'];
            }
        }

        return [];
    }

    /**
     * Check accessibility compliance
     */
    protected function checkAccessibility(array $content): array
    {
        $issues = [];

        // Check for video lessons without transcripts
        foreach ($content['lessons'] as $lesson) {
            if ($lesson['content_type'] === 'video') {
                $issues[] = [
                    'type' => 'missing_transcript',
                    'severity' => 'high',
                    'lesson' => $lesson['title'],
                    'description' => 'Video content should include transcripts for accessibility',
                ];
            }
        }

        // Check for very long lessons (over 30 minutes)
        foreach ($content['lessons'] as $lesson) {
            if (isset($lesson['duration']) && $lesson['duration'] > 30) {
                $issues[] = [
                    'type' => 'long_content',
                    'severity' => 'medium',
                    'lesson' => $lesson['title'],
                    'description' => 'Consider breaking long lessons into smaller segments',
                ];
            }
        }

        return $issues;
    }

    /**
     * Calculate overall quality score
     */
    protected function calculateOverallScore(array $readability, array $gaps, array $accessibilityIssues): float
    {
        $readabilityScore = $readability['readability_score'] ?? 70;
        $engagementScore = $readability['engagement_score'] ?? 70;
        
        // Deduct points for gaps
        $gapPenalty = count($gaps['missing_topics'] ?? []) * 5;
        $gapPenalty += count($gaps['progression_gaps'] ?? []) * 3;
        
        // Deduct points for accessibility issues
        $accessibilityPenalty = 0;
        foreach ($accessibilityIssues as $issue) {
            $accessibilityPenalty += $issue['severity'] === 'high' ? 5 : 2;
        }

        $score = ($readabilityScore * 0.4) + ($engagementScore * 0.4);
        $score -= $gapPenalty;
        $score -= $accessibilityPenalty;

        return max(0, min(100, $score));
    }

    /**
     * Store analysis results in database
     */
    protected function storeAnalysis(Course $course, array $data): ContentAnalysis
    {
        return ContentAnalysis::create([
            'course_id' => $course->id,
            'readability_score' => $data['readability_score']['readability_score'] ?? 70,
            'engagement_score' => $data['readability_score']['engagement_score'] ?? 70,
            'overall_score' => $data['overall_score'],
            'complexity_level' => $data['readability_score']['complexity_level'] ?? 'intermediate',
            'content_gaps' => $data['content_gaps'],
            'suggestions' => $data['suggestions'],
            'accessibility_issues' => $data['accessibility_issues'],
            'analyzed_at' => now(),
        ]);
    }

    /**
     * Re-analyze course content
     */
    public function reAnalyzeCourse(Course $course): ContentAnalysis
    {
        // Mark previous analyses as outdated
        ContentAnalysis::where('course_id', $course->id)
            ->update(['is_current' => false]);

        // Perform new analysis
        return $this->analyzeCourse($course);
    }

    /**
     * Get latest analysis for a course
     */
    public function getLatestAnalysis(Course $course): ?ContentAnalysis
    {
        return ContentAnalysis::where('course_id', $course->id)
            ->where('is_current', true)
            ->latest()
            ->first();
    }
}
