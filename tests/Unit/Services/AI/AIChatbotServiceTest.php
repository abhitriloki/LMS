<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\AIChatbotService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\AIRecommendation;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

class AIChatbotServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AIChatbotService $chatbotService;
    protected $mockAIService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock AI service
        $this->mockAIService = Mockery::mock(AIServiceInterface::class);
        $this->chatbotService = new AIChatbotService($this->mockAIService);

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'role' => 'employee',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_detects_greeting_intent()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn('greeting');

        $intent = $this->chatbotService->detectIntent('Hello!');

        $this->assertEquals('greeting', $intent);
    }

    /** @test */
    public function it_detects_course_recommendation_intent()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn('course_recommendation');

        $intent = $this->chatbotService->detectIntent('Can you recommend some courses for me?');

        $this->assertEquals('course_recommendation', $intent);
    }

    /** @test */
    public function it_detects_progress_inquiry_intent()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn('progress_inquiry');

        $intent = $this->chatbotService->detectIntent('What is my progress?');

        $this->assertEquals('progress_inquiry', $intent);
    }

    /** @test */
    public function it_detects_enrollment_assistance_intent()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn('enrollment_assistance');

        $intent = $this->chatbotService->detectIntent('I want to enroll in a course');

        $this->assertEquals('enrollment_assistance', $intent);
    }

    /** @test */
    public function it_caches_intent_detection_results()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn('greeting');

        // First call should hit the AI service
        $intent1 = $this->chatbotService->detectIntent('Hello!');
        
        // Second call should use cache
        $intent2 = $this->chatbotService->detectIntent('Hello!');

        $this->assertEquals('greeting', $intent1);
        $this->assertEquals('greeting', $intent2);
    }

    /** @test */
    public function it_processes_message_and_returns_response()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('greeting');

        $result = $this->chatbotService->processMessage('Hello!', $this->user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('intent', $result);
        $this->assertArrayHasKey('entities', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertStringContainsString($this->user->name, $result['response']);
    }

    /** @test */
    public function it_handles_greeting_messages()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('greeting');

        $result = $this->chatbotService->processMessage('Hi there!', $this->user);

        $this->assertEquals('greeting', $result['intent']);
        $this->assertStringContainsString($this->user->name, $result['response']);
    }

    /** @test */
    public function it_handles_help_requests()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('help');

        $result = $this->chatbotService->processMessage('What can you help me with?', $this->user);

        $this->assertEquals('help', $result['intent']);
        $this->assertStringContainsString('Course Recommendations', $result['response']);
        $this->assertStringContainsString('Progress Tracking', $result['response']);
        $this->assertStringContainsString('Course Enrollment', $result['response']);
    }

    /** @test */
    public function it_provides_course_recommendations_with_ai_recommendations()
    {
        Cache::flush();

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
        ]);

        AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'status' => 'pending',
            'relevance_score' => 0.95,
            'reasoning' => 'Perfect match for your skills',
            'expires_at' => now()->addDays(7),
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('course_recommendation');

        $result = $this->chatbotService->processMessage('Recommend courses', $this->user);

        $this->assertEquals('course_recommendation', $result['intent']);
        $this->assertStringContainsString($course->title, $result['response']);
        $this->assertStringContainsString('Perfect match for your skills', $result['response']);
    }

    /** @test */
    public function it_shows_user_progress_summary()
    {
        Cache::flush();

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create(['category_id' => $category->id]);

        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'status' => 'in_progress',
            'progress_percentage' => 50,
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('progress_inquiry');

        $result = $this->chatbotService->processMessage('What is my progress?', $this->user);

        $this->assertEquals('progress_inquiry', $result['intent']);
        $this->assertStringContainsString('Total Courses Enrolled', $result['response']);
        $this->assertStringContainsString('50', $result['response']);
    }

    /** @test */
    public function it_shows_message_when_no_enrollments()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('progress_inquiry');

        $result = $this->chatbotService->processMessage('What is my progress?', $this->user);

        $this->assertStringContainsString("haven't enrolled", $result['response']);
    }

    /** @test */
    public function it_handles_enrollment_assistance_with_course_id()
    {
        Cache::flush();

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
            'difficulty_level' => 'beginner',
            'estimated_duration' => 120,
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('enrollment_assistance');

        $result = $this->chatbotService->processMessage("Enroll in course {$course->id}", $this->user);

        $this->assertEquals('enrollment_assistance', $result['intent']);
        $this->assertStringContainsString($course->title, $result['response']);
        $this->assertStringContainsString('beginner', $result['response']);
    }

    /** @test */
    public function it_detects_already_enrolled_status()
    {
        Cache::flush();

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
        ]);

        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'status' => 'in_progress',
            'progress_percentage' => 30,
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('enrollment_assistance');

        $result = $this->chatbotService->processMessage("Enroll in course {$course->id}", $this->user);

        $this->assertStringContainsString('already enrolled', $result['response']);
        $this->assertStringContainsString('30%', $result['response']);
    }

    /** @test */
    public function it_checks_prerequisites_before_enrollment()
    {
        Cache::flush();

        $category = CourseCategory::factory()->create();
        
        $prerequisiteCourse = Course::factory()->create([
            'category_id' => $category->id,
            'title' => 'Prerequisite Course',
        ]);

        $course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
            'prerequisites' => [$prerequisiteCourse->id],
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('enrollment_assistance');

        $result = $this->chatbotService->processMessage("Enroll in course {$course->id}", $this->user);

        $this->assertStringContainsString('prerequisite', $result['response']);
        $this->assertStringContainsString($prerequisiteCourse->title, $result['response']);
    }

    /** @test */
    public function it_handles_enrollment_by_course_title()
    {
        Cache::flush();

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'title' => 'Python Programming',
            'is_published' => true,
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('enrollment_assistance');

        $result = $this->chatbotService->processMessage('Enroll in Python Programming', $this->user);

        $this->assertStringContainsString('Python Programming', $result['response']);
    }

    /** @test */
    public function it_shows_multiple_courses_when_title_matches_many()
    {
        Cache::flush();

        $category = CourseCategory::factory()->create();
        
        Course::factory()->create([
            'category_id' => $category->id,
            'title' => 'Python Basics',
            'is_published' => true,
        ]);

        Course::factory()->create([
            'category_id' => $category->id,
            'title' => 'Python Advanced',
            'is_published' => true,
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('enrollment_assistance');

        $result = $this->chatbotService->processMessage('Enroll in Python', $this->user);

        $this->assertStringContainsString('found', $result['response']);
        $this->assertStringContainsString('Python Basics', $result['response']);
        $this->assertStringContainsString('Python Advanced', $result['response']);
    }

    /** @test */
    public function it_searches_knowledge_base()
    {
        $category = CourseCategory::factory()->create();
        
        $enrolledCourse = Course::factory()->create([
            'category_id' => $category->id,
            'title' => 'Enrolled Course',
        ]);

        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $enrolledCourse->id,
        ]);

        $availableCourse = Course::factory()->create([
            'category_id' => $category->id,
            'title' => 'Available Course',
            'is_published' => true,
        ]);

        $results = $this->chatbotService->searchKnowledgeBase('Course', $this->user);

        $this->assertArrayHasKey('enrolled_courses', $results);
        $this->assertArrayHasKey('available_courses', $results);
        $this->assertArrayHasKey('user_progress', $results);
    }

    /** @test */
    public function it_handles_specific_course_progress_inquiry()
    {
        Cache::flush();

        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'category_id' => $category->id,
            'title' => 'Test Course',
        ]);

        $module = CourseModule::factory()->create([
            'course_id' => $course->id,
        ]);

        CourseLesson::factory()->count(5)->create([
            'module_id' => $module->id,
        ]);

        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'status' => 'in_progress',
            'progress_percentage' => 60,
            'last_accessed_at' => now()->subDays(2),
        ]);

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('progress_inquiry');

        $result = $this->chatbotService->processMessage("What is my progress in course {$course->id}?", $this->user);

        $this->assertStringContainsString($course->title, $result['response']);
        $this->assertStringContainsString('60%', $result['response']);
        $this->assertStringContainsString('in progress', strtolower($result['response']));
    }

    /** @test */
    public function it_returns_fallback_response_on_error()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->andThrow(new \Exception('AI service error'));

        $result = $this->chatbotService->processMessage('Test message', $this->user);

        $this->assertEquals('unknown', $result['intent']);
        $this->assertStringContainsString('having trouble', $result['response']);
        $this->assertEquals(0.0, $result['confidence']);
    }

    /** @test */
    public function it_calculates_confidence_scores()
    {
        Cache::flush();

        $this->mockAIService->shouldReceive('generateText')
            ->andReturn('greeting');

        $result = $this->chatbotService->processMessage('Hello!', $this->user);

        $this->assertIsFloat($result['confidence']);
        $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['confidence']);
    }
}
