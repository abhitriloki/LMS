<?php

namespace Tests\Unit\Services\AI;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\AIRecommendation;
use App\Services\AI\AIRecommendationService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AIRecommendationService $service;
    protected $mockAIService;
    protected User $user;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAIService = $this->mock(AIServiceInterface::class);
        $this->service = new AIRecommendationService($this->mockAIService);

        $category = CourseCategory::factory()->create();
        $this->course = Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
        ]);

        $this->user = User::factory()->create([
            'role' => 'employee',
        ]);
    }

    /** @test */
    public function it_analyzes_user_profile_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('analyzeUserProfile');
        $method->setAccessible(true);

        $profile = $method->invoke($this->service, $this->user);

        $this->assertIsArray($profile);
        $this->assertArrayHasKey('role', $profile);
        $this->assertArrayHasKey('position', $profile);
        $this->assertArrayHasKey('department', $profile);
        $this->assertEquals($this->user->role, $profile['role']);
    }

    /** @test */
    public function it_analyzes_learning_history_with_completed_courses()
    {
        // Create completed enrollment
        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'completed',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('analyzeLearningHistory');
        $method->setAccessible(true);

        $history = $method->invoke($this->service, $this->user);

        $this->assertIsArray($history);
        $this->assertEquals(1, $history['completed_courses_count']);
        $this->assertArrayHasKey('categories_completed', $history);
        $this->assertArrayHasKey('average_assessment_score', $history);
    }

    /** @test */
    public function it_identifies_skill_gaps_for_new_user()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('identifySkillGaps');
        $method->setAccessible(true);

        $skillGaps = $method->invoke($this->service, $this->user);

        $this->assertIsArray($skillGaps);
        $this->assertArrayHasKey('missing_categories', $skillGaps);
        $this->assertArrayHasKey('not_taken_courses_count', $skillGaps);
        $this->assertArrayHasKey('suggested_difficulty', $skillGaps);
    }

    /** @test */
    public function it_suggests_beginner_difficulty_for_new_users()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('suggestNextDifficulty');
        $method->setAccessible(true);

        $difficulty = $method->invoke($this->service, $this->user);

        $this->assertEquals('beginner', $difficulty);
    }

    /** @test */
    public function it_builds_recommendation_prompt_correctly()
    {
        $userProfile = [
            'role' => 'employee',
            'position' => 'Developer',
            'department' => 'IT',
        ];

        $learningHistory = [
            'completed_courses_count' => 2,
            'in_progress_courses_count' => 1,
            'average_assessment_score' => 85.5,
            'categories_completed' => ['Programming'],
            'completed_courses' => ['PHP Basics'],
        ];

        $skillGaps = [
            'missing_categories' => ['Database Design'],
            'suggested_difficulty' => 'intermediate',
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildRecommendationPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($this->service, $this->user, $userProfile, $learningHistory, $skillGaps);

        $this->assertIsString($prompt);
        $this->assertStringContainsString('Role: employee', $prompt);
        $this->assertStringContainsString('Position: Developer', $prompt);
        $this->assertStringContainsString('Completed Courses: 2', $prompt);
        $this->assertStringContainsString('Average Assessment Score: 85.5%', $prompt);
    }

    /** @test */
    public function it_parses_valid_ai_response()
    {
        $aiResponse = json_encode([
            'recommendations' => [
                [
                    'course_id' => $this->course->id,
                    'score' => 0.95,
                    'reasoning' => 'Great match for your skills.'
                ]
            ]
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseRecommendations');
        $method->setAccessible(true);

        $recommendations = $method->invoke($this->service, $aiResponse, $this->user);

        $this->assertIsArray($recommendations);
        $this->assertCount(1, $recommendations);
        $this->assertEquals($this->course->id, $recommendations[0]['course_id']);
        $this->assertEquals(0.95, $recommendations[0]['score']);
    }

    /** @test */
    public function it_handles_invalid_ai_response_gracefully()
    {
        $aiResponse = 'Invalid JSON response';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseRecommendations');
        $method->setAccessible(true);

        $recommendations = $method->invoke($this->service, $aiResponse, $this->user);

        $this->assertIsArray($recommendations);
        $this->assertEmpty($recommendations);
    }

    /** @test */
    public function it_stores_recommendations_correctly()
    {
        $recommendations = [
            [
                'course_id' => $this->course->id,
                'score' => 0.95,
                'reasoning' => 'Perfect match.'
            ]
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('storeRecommendations');
        $method->setAccessible(true);

        $method->invoke($this->service, $this->user, $recommendations, 5);

        $this->assertDatabaseHas('ai_recommendations', [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_expires_old_recommendations_before_storing_new_ones()
    {
        // Create old recommendation
        $oldRec = AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $recommendations = [
            [
                'course_id' => $this->course->id,
                'score' => 0.90,
                'reasoning' => 'New recommendation.'
            ]
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('storeRecommendations');
        $method->setAccessible(true);

        $method->invoke($this->service, $this->user, $recommendations, 5);

        $oldRec->refresh();
        $this->assertEquals('expired', $oldRec->status);
    }

    /** @test */
    public function it_gets_available_courses_excluding_enrolled()
    {
        // Enroll user in a course
        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
        ]);

        // Create another available course
        $availableCourse = Course::factory()->create([
            'is_published' => true,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAvailableCourses');
        $method->setAccessible(true);

        $courses = $method->invoke($this->service, $this->user);

        $this->assertFalse($courses->contains('id', $this->course->id));
        $this->assertTrue($courses->contains('id', $availableCourse->id));
    }

    /** @test */
    public function it_gets_personalized_courses()
    {
        AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'score' => 0.95,
        ]);

        $courses = $this->service->getPersonalizedCourses($this->user, 10);

        $this->assertCount(1, $courses);
        $this->assertEquals($this->course->id, $courses->first()->id);
    }

    /** @test */
    public function it_records_accept_feedback()
    {
        $recommendation = AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $this->service->recordFeedback($recommendation, 'accept', 'Very helpful!');

        $recommendation->refresh();
        $this->assertEquals('accepted', $recommendation->status);
        $this->assertEquals('Very helpful!', $recommendation->feedback);
    }

    /** @test */
    public function it_records_reject_feedback()
    {
        $recommendation = AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $this->service->recordFeedback($recommendation, 'reject', 'Not relevant.');

        $recommendation->refresh();
        $this->assertEquals('rejected', $recommendation->status);
        $this->assertEquals('Not relevant.', $recommendation->feedback);
    }

    /** @test */
    public function it_checks_if_user_needs_new_recommendations()
    {
        // No recommendations - should need new
        $this->assertTrue($this->service->needsNewRecommendations($this->user));

        // Create 3 active recommendations
        AIRecommendation::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        // Should not need new
        $this->assertFalse($this->service->needsNewRecommendations($this->user));

        // Create only 2 active recommendations
        AIRecommendation::where('user_id', $this->user->id)->delete();
        AIRecommendation::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        // Should need new
        $this->assertTrue($this->service->needsNewRecommendations($this->user));
    }

    /** @test */
    public function it_throws_exception_on_ai_service_failure()
    {
        $this->mockAIService->shouldReceive('generateText')
            ->once()
            ->andThrow(new AIServiceException('AI service unavailable'));

        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Failed to generate recommendations');

        $this->service->generateRecommendations($this->user);
    }
}
