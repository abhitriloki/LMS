<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\ContentAnalysis;
use App\Services\AI\AIContentAnalyzerService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AIContentAnalyzerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AIContentAnalyzerService $service;
    protected $mockAIService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAIService = Mockery::mock(AIServiceInterface::class);
        $this->service = new AIContentAnalyzerService($this->mockAIService);
    }

    public function test_analyze_course_creates_content_analysis()
    {
        $course = Course::factory()->create([
            'title' => 'Test Course',
            'description' => 'Test description',
            'difficulty_level' => 'intermediate',
        ]);

        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create([
            'module_id' => $module->id,
            'content_type' => 'text',
            'content' => 'This is test lesson content.',
        ]);

        // Mock AI responses
        $this->mockAIService->shouldReceive('generateText')
            ->times(3)
            ->andReturn(
                json_encode([
                    'readability_score' => 75,
                    'complexity_level' => 'intermediate',
                    'avg_sentence_length' => 15,
                    'vocabulary_complexity' => 'moderate',
                    'engagement_score' => 80,
                    'issues' => [],
                ]),
                json_encode([
                    'missing_topics' => ['Advanced concepts'],
                    'progression_gaps' => [],
                    'needs_more_depth' => [],
                    'missing_prerequisites' => [],
                ]),
                json_encode([
                    'suggestions' => [
                        [
                            'category' => 'readability',
                            'priority' => 'medium',
                            'suggestion' => 'Add more examples',
                            'impact' => 'Improved understanding',
                        ],
                    ],
                ])
            );

        $analysis = $this->service->analyzeCourse($course);

        $this->assertInstanceOf(ContentAnalysis::class, $analysis);
        $this->assertEquals($course->id, $analysis->course_id);
        $this->assertNotNull($analysis->overall_score);
        $this->assertNotNull($analysis->readability_score);
        $this->assertNotNull($analysis->engagement_score);
    }

    public function test_analyze_readability_returns_scores()
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create([
            'module_id' => $module->id,
            'content_type' => 'text',
            'content' => 'Test content',
        ]);

        $mockResponse = json_encode([
            'readability_score' => 85,
            'complexity_level' => 'beginner',
            'avg_sentence_length' => 12,
            'vocabulary_complexity' => 'simple',
            'engagement_score' => 90,
            'issues' => ['Some issue'],
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn($mockResponse);

        $content = $this->service->analyzeCourse($course);

        $this->assertGreaterThan(0, $content->readability_score);
    }

    public function test_identify_content_gaps_finds_missing_topics()
    {
        $course = Course::factory()->create([
            'learning_objectives' => [
                'Understand basic concepts',
                'Master advanced techniques',
            ],
        ]);

        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Basic Concepts',
        ]);

        $mockResponse = json_encode([
            'missing_topics' => ['Advanced techniques'],
            'progression_gaps' => ['Gap between basic and advanced'],
            'needs_more_depth' => ['Basic concepts'],
            'missing_prerequisites' => [],
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->times(3)
            ->andReturn(
                json_encode(['readability_score' => 70, 'engagement_score' => 70, 'complexity_level' => 'intermediate', 'issues' => []]),
                $mockResponse,
                json_encode(['suggestions' => []])
            );

        $analysis = $this->service->analyzeCourse($course);

        $this->assertNotEmpty($analysis->content_gaps);
        $this->assertArrayHasKey('missing_topics', $analysis->content_gaps);
    }

    public function test_check_accessibility_identifies_video_without_transcript()
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create([
            'module_id' => $module->id,
            'content_type' => 'video',
            'title' => 'Video Lesson',
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->times(3)
            ->andReturn(
                json_encode(['readability_score' => 70, 'engagement_score' => 70, 'complexity_level' => 'intermediate', 'issues' => []]),
                json_encode(['missing_topics' => [], 'progression_gaps' => [], 'needs_more_depth' => [], 'missing_prerequisites' => []]),
                json_encode(['suggestions' => []])
            );

        $analysis = $this->service->analyzeCourse($course);

        $this->assertNotEmpty($analysis->accessibility_issues);
        $this->assertEquals('missing_transcript', $analysis->accessibility_issues[0]['type']);
    }

    public function test_check_accessibility_identifies_long_lessons()
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create([
            'module_id' => $module->id,
            'content_type' => 'video',
            'duration' => 45, // Over 30 minutes
            'title' => 'Long Video',
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->times(3)
            ->andReturn(
                json_encode(['readability_score' => 70, 'engagement_score' => 70, 'complexity_level' => 'intermediate', 'issues' => []]),
                json_encode(['missing_topics' => [], 'progression_gaps' => [], 'needs_more_depth' => [], 'missing_prerequisites' => []]),
                json_encode(['suggestions' => []])
            );

        $analysis = $this->service->analyzeCourse($course);

        $this->assertNotEmpty($analysis->accessibility_issues);
        $longContentIssue = collect($analysis->accessibility_issues)->firstWhere('type', 'long_content');
        $this->assertNotNull($longContentIssue);
    }

    public function test_re_analyze_course_marks_previous_as_not_current()
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create(['module_id' => $module->id]);

        // Create initial analysis
        $oldAnalysis = ContentAnalysis::create([
            'course_id' => $course->id,
            'readability_score' => 70,
            'engagement_score' => 70,
            'overall_score' => 70,
            'complexity_level' => 'intermediate',
            'content_gaps' => [],
            'suggestions' => [],
            'accessibility_issues' => [],
            'is_current' => true,
            'analyzed_at' => now(),
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->times(3)
            ->andReturn(
                json_encode(['readability_score' => 80, 'engagement_score' => 80, 'complexity_level' => 'intermediate', 'issues' => []]),
                json_encode(['missing_topics' => [], 'progression_gaps' => [], 'needs_more_depth' => [], 'missing_prerequisites' => []]),
                json_encode(['suggestions' => []])
            );

        $newAnalysis = $this->service->reAnalyzeCourse($course);

        $this->assertFalse($oldAnalysis->fresh()->is_current);
        $this->assertTrue($newAnalysis->is_current);
    }

    public function test_get_latest_analysis_returns_current_analysis()
    {
        $course = Course::factory()->create();

        ContentAnalysis::create([
            'course_id' => $course->id,
            'readability_score' => 70,
            'engagement_score' => 70,
            'overall_score' => 70,
            'complexity_level' => 'intermediate',
            'content_gaps' => [],
            'suggestions' => [],
            'accessibility_issues' => [],
            'is_current' => false,
            'analyzed_at' => now()->subDays(2),
        ]);

        $currentAnalysis = ContentAnalysis::create([
            'course_id' => $course->id,
            'readability_score' => 80,
            'engagement_score' => 80,
            'overall_score' => 80,
            'complexity_level' => 'intermediate',
            'content_gaps' => [],
            'suggestions' => [],
            'accessibility_issues' => [],
            'is_current' => true,
            'analyzed_at' => now(),
        ]);

        $latest = $this->service->getLatestAnalysis($course);

        $this->assertEquals($currentAnalysis->id, $latest->id);
    }

    public function test_analyze_course_throws_exception_on_ai_failure()
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create(['module_id' => $module->id]);

        $this->mockAIService->shouldReceive('generateText')
            ->once()
            ->andThrow(new \Exception('AI service error'));

        $this->expectException(AIServiceException::class);

        $this->service->analyzeCourse($course);
    }

    public function test_calculate_overall_score_considers_all_factors()
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create([
            'module_id' => $module->id,
            'content_type' => 'video',
            'duration' => 45,
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->times(3)
            ->andReturn(
                json_encode(['readability_score' => 90, 'engagement_score' => 85, 'complexity_level' => 'intermediate', 'issues' => []]),
                json_encode(['missing_topics' => ['Topic 1', 'Topic 2'], 'progression_gaps' => ['Gap 1'], 'needs_more_depth' => [], 'missing_prerequisites' => []]),
                json_encode(['suggestions' => []])
            );

        $analysis = $this->service->analyzeCourse($course);

        // Score should be reduced due to gaps and accessibility issues
        $this->assertLessThan(90, $analysis->overall_score);
        $this->assertGreaterThan(0, $analysis->overall_score);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
