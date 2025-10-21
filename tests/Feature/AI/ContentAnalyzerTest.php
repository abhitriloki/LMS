<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\ContentAnalysis;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ContentAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AI service
        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateText')
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
                    'missing_topics' => [],
                    'progression_gaps' => [],
                    'needs_more_depth' => [],
                    'missing_prerequisites' => [],
                ]),
                json_encode([
                    'suggestions' => [
                        [
                            'category' => 'readability',
                            'priority' => 'medium',
                            'suggestion' => 'Test suggestion',
                            'impact' => 'Test impact',
                        ],
                    ],
                ])
            );

        $this->app->instance(AIServiceInterface::class, $mockAIService);
    }

    public function test_instructor_can_view_content_analyzer_page()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $response = $this->actingAs($instructor)
            ->get(route('admin.content-analyzer.show', $course));

        $response->assertStatus(200);
        $response->assertViewIs('admin.content-analyzer.show');
        $response->assertViewHas('course', $course);
    }

    public function test_instructor_can_trigger_content_analysis()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create(['module_id' => $module->id]);

        $response = $this->actingAs($instructor)
            ->post(route('admin.content-analyzer.analyze', $course));

        $response->assertRedirect(route('admin.content-analyzer.show', $course));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_analysis', [
            'course_id' => $course->id,
        ]);
    }

    public function test_instructor_can_re_analyze_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
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

        $response = $this->actingAs($instructor)
            ->post(route('admin.content-analyzer.re-analyze', $course));

        $response->assertRedirect(route('admin.content-analyzer.show', $course));
        $response->assertSessionHas('success');

        // Old analysis should be marked as not current
        $this->assertDatabaseHas('content_analysis', [
            'id' => $oldAnalysis->id,
            'is_current' => false,
        ]);

        // New analysis should exist
        $this->assertEquals(2, ContentAnalysis::where('course_id', $course->id)->count());
    }

    public function test_instructor_can_view_analysis_history()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        // Create multiple analyses
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

        ContentAnalysis::create([
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

        $response = $this->actingAs($instructor)
            ->get(route('admin.content-analyzer.history', $course));

        $response->assertStatus(200);
        $response->assertViewIs('admin.content-analyzer.history');
        $response->assertViewHas('analyses');
    }

    public function test_student_cannot_access_content_analyzer()
    {
        $student = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create();

        $response = $this->actingAs($student)
            ->get(route('admin.content-analyzer.show', $course));

        $response->assertStatus(403);
    }

    public function test_instructor_cannot_analyze_other_instructors_course()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor2->id]);

        $response = $this->actingAs($instructor1)
            ->post(route('admin.content-analyzer.analyze', $course));

        $response->assertStatus(403);
    }

    public function test_admin_can_analyze_any_course()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        CourseLesson::factory()->create(['module_id' => $module->id]);

        $response = $this->actingAs($admin)
            ->post(route('admin.content-analyzer.analyze', $course));

        $response->assertRedirect(route('admin.content-analyzer.show', $course));
        $response->assertSessionHas('success');
    }

    public function test_analysis_displays_readability_scores()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $analysis = ContentAnalysis::create([
            'course_id' => $course->id,
            'readability_score' => 85.5,
            'engagement_score' => 90.0,
            'overall_score' => 87.5,
            'complexity_level' => 'intermediate',
            'content_gaps' => [],
            'suggestions' => [],
            'accessibility_issues' => [],
            'is_current' => true,
            'analyzed_at' => now(),
        ]);

        $response = $this->actingAs($instructor)
            ->get(route('admin.content-analyzer.show', $course));

        $response->assertStatus(200);
        $response->assertSee('85.5');
        $response->assertSee('90.0');
        $response->assertSee('87.5');
    }

    public function test_analysis_displays_content_gaps()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $analysis = ContentAnalysis::create([
            'course_id' => $course->id,
            'readability_score' => 75,
            'engagement_score' => 75,
            'overall_score' => 75,
            'complexity_level' => 'intermediate',
            'content_gaps' => [
                'missing_topics' => ['Advanced concepts', 'Best practices'],
                'progression_gaps' => ['Gap between modules 2 and 3'],
            ],
            'suggestions' => [],
            'accessibility_issues' => [],
            'is_current' => true,
            'analyzed_at' => now(),
        ]);

        $response = $this->actingAs($instructor)
            ->get(route('admin.content-analyzer.show', $course));

        $response->assertStatus(200);
        $response->assertSee('Advanced concepts');
        $response->assertSee('Best practices');
        $response->assertSee('Gap between modules 2 and 3');
    }

    public function test_analysis_displays_suggestions()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $analysis = ContentAnalysis::create([
            'course_id' => $course->id,
            'readability_score' => 75,
            'engagement_score' => 75,
            'overall_score' => 75,
            'complexity_level' => 'intermediate',
            'content_gaps' => [],
            'suggestions' => [
                [
                    'category' => 'readability',
                    'priority' => 'high',
                    'suggestion' => 'Simplify complex sentences',
                    'impact' => 'Improved comprehension',
                ],
            ],
            'accessibility_issues' => [],
            'is_current' => true,
            'analyzed_at' => now(),
        ]);

        $response = $this->actingAs($instructor)
            ->get(route('admin.content-analyzer.show', $course));

        $response->assertStatus(200);
        $response->assertSee('Simplify complex sentences');
        $response->assertSee('Improved comprehension');
    }

    public function test_analysis_displays_accessibility_issues()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $analysis = ContentAnalysis::create([
            'course_id' => $course->id,
            'readability_score' => 75,
            'engagement_score' => 75,
            'overall_score' => 75,
            'complexity_level' => 'intermediate',
            'content_gaps' => [],
            'suggestions' => [],
            'accessibility_issues' => [
                [
                    'type' => 'missing_transcript',
                    'severity' => 'high',
                    'lesson' => 'Introduction Video',
                    'description' => 'Video content should include transcripts',
                ],
            ],
            'is_current' => true,
            'analyzed_at' => now(),
        ]);

        $response = $this->actingAs($instructor)
            ->get(route('admin.content-analyzer.show', $course));

        $response->assertStatus(200);
        $response->assertSee('missing_transcript');
        $response->assertSee('Introduction Video');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
