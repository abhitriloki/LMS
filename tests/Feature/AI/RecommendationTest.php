<?php

namespace Tests\Feature\AI;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\AIRecommendation;
use App\Services\AI\AIRecommendationService;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Course $course;
    protected CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->category = CourseCategory::factory()->create([
            'name' => 'Programming',
        ]);

        $this->course = Course::factory()->create([
            'title' => 'Advanced PHP',
            'category_id' => $this->category->id,
            'difficulty_level' => 'intermediate',
            'is_published' => true,
        ]);

        $this->user = User::factory()->create([
            'role' => 'employee',
            'position' => 'Developer',
        ]);
    }

    /** @test */
    public function it_generates_recommendations_for_user()
    {
        // Mock AI service response
        $mockAIResponse = json_encode([
            'recommendations' => [
                [
                    'course_id' => $this->course->id,
                    'score' => 0.95,
                    'reasoning' => 'This course matches your skill level and interests in programming.'
                ]
            ]
        ]);

        $mockAIService = $this->mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn($mockAIResponse);

        $service = new AIRecommendationService($mockAIService);
        $recommendations = $service->generateRecommendations($this->user, 5);

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        $this->assertEquals($this->course->id, $recommendations[0]['course_id']);
        $this->assertEquals(0.95, $recommendations[0]['score']);
    }

    /** @test */
    public function it_stores_recommendations_in_database()
    {
        $mockAIResponse = json_encode([
            'recommendations' => [
                [
                    'course_id' => $this->course->id,
                    'score' => 0.95,
                    'reasoning' => 'Great match for your profile.'
                ]
            ]
        ]);

        $mockAIService = $this->mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn($mockAIResponse);

        $service = new AIRecommendationService($mockAIService);
        $service->generateRecommendations($this->user, 5);

        $this->assertDatabaseHas('ai_recommendations', [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_expires_old_recommendations_when_generating_new_ones()
    {
        // Create old recommendation
        $oldRecommendation = AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $mockAIResponse = json_encode([
            'recommendations' => [
                [
                    'course_id' => $this->course->id,
                    'score' => 0.90,
                    'reasoning' => 'Updated recommendation.'
                ]
            ]
        ]);

        $mockAIService = $this->mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn($mockAIResponse);

        $service = new AIRecommendationService($mockAIService);
        $service->generateRecommendations($this->user, 5);

        $oldRecommendation->refresh();
        $this->assertEquals('expired', $oldRecommendation->status);
    }

    /** @test */
    public function it_excludes_enrolled_courses_from_recommendations()
    {
        // Enroll user in a course
        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $mockAIService = $this->mock(AIServiceInterface::class);
        $service = new AIRecommendationService($mockAIService);

        $availableCourses = $service->getAvailableCourses($this->user);

        $this->assertFalse($availableCourses->contains('id', $this->course->id));
    }

    /** @test */
    public function it_analyzes_user_learning_history()
    {
        // Create completed enrollment
        $completedCourse = Course::factory()->create([
            'category_id' => $this->category->id,
            'is_published' => true,
        ]);

        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $completedCourse->id,
            'status' => 'completed',
        ]);

        $mockAIService = $this->mock(AIServiceInterface::class);
        $service = new AIRecommendationService($mockAIService);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('analyzeLearningHistory');
        $method->setAccessible(true);

        $history = $method->invoke($service, $this->user);

        $this->assertEquals(1, $history['completed_courses_count']);
        $this->assertContains($completedCourse->title, $history['completed_courses']);
    }

    /** @test */
    public function it_identifies_skill_gaps()
    {
        $mockAIService = $this->mock(AIServiceInterface::class);
        $service = new AIRecommendationService($mockAIService);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('identifySkillGaps');
        $method->setAccessible(true);

        $skillGaps = $method->invoke($service, $this->user);

        $this->assertIsArray($skillGaps);
        $this->assertArrayHasKey('missing_categories', $skillGaps);
        $this->assertArrayHasKey('suggested_difficulty', $skillGaps);
    }

    /** @test */
    public function user_can_view_recommendations_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('recommendations.index'));

        $response->assertStatus(200);
        $response->assertViewIs('recommendations.index');
    }

    /** @test */
    public function user_can_accept_recommendation()
    {
        $recommendation = AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('recommendations.accept', $recommendation), [
            'feedback' => 'Looks interesting!',
        ]);

        $response->assertRedirect();
        $recommendation->refresh();
        $this->assertEquals('accepted', $recommendation->status);
        $this->assertEquals('Looks interesting!', $recommendation->feedback);
    }

    /** @test */
    public function user_can_reject_recommendation()
    {
        $recommendation = AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('recommendations.reject', $recommendation), [
            'feedback' => 'Not relevant to me.',
        ]);

        $response->assertRedirect();
        $recommendation->refresh();
        $this->assertEquals('rejected', $recommendation->status);
        $this->assertEquals('Not relevant to me.', $recommendation->feedback);
    }

    /** @test */
    public function user_cannot_accept_another_users_recommendation()
    {
        $otherUser = User::factory()->create();
        $recommendation = AIRecommendation::factory()->create([
            'user_id' => $otherUser->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('recommendations.accept', $recommendation));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_gets_active_recommendations_for_user()
    {
        // Create active recommendation
        AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'expires_at' => now()->addDays(30),
        ]);

        // Create expired recommendation
        AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'expires_at' => now()->subDays(1),
        ]);

        $mockAIService = $this->mock(AIServiceInterface::class);
        $service = new AIRecommendationService($mockAIService);

        $activeRecommendations = $service->getActiveRecommendations($this->user);

        $this->assertEquals(1, $activeRecommendations->count());
    }

    /** @test */
    public function it_checks_if_user_needs_new_recommendations()
    {
        $mockAIService = $this->mock(AIServiceInterface::class);
        $service = new AIRecommendationService($mockAIService);

        // User with no recommendations
        $needsNew = $service->needsNewRecommendations($this->user);
        $this->assertTrue($needsNew);

        // Create 3 active recommendations
        for ($i = 0; $i < 3; $i++) {
            $course = Course::factory()->create(['is_published' => true]);
            AIRecommendation::factory()->create([
                'user_id' => $this->user->id,
                'course_id' => $course->id,
                'status' => 'active',
            ]);
        }

        $needsNew = $service->needsNewRecommendations($this->user);
        $this->assertFalse($needsNew);
    }

    /** @test */
    public function it_records_feedback_on_recommendation()
    {
        $recommendation = AIRecommendation::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $mockAIService = $this->mock(AIServiceInterface::class);
        $service = new AIRecommendationService($mockAIService);

        $service->recordFeedback($recommendation, 'accept', 'Very helpful!');

        $recommendation->refresh();
        $this->assertEquals('accepted', $recommendation->status);
        $this->assertEquals('Very helpful!', $recommendation->feedback);
    }

    /** @test */
    public function it_suggests_appropriate_difficulty_level()
    {
        $mockAIService = $this->mock(AIServiceInterface::class);
        $service = new AIRecommendationService($mockAIService);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('suggestNextDifficulty');
        $method->setAccessible(true);

        // New user should get beginner
        $difficulty = $method->invoke($service, $this->user);
        $this->assertEquals('beginner', $difficulty);

        // User with completed intermediate courses and good scores
        $intermediateCourse = Course::factory()->create([
            'difficulty_level' => 'intermediate',
            'is_published' => true,
        ]);

        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $intermediateCourse->id,
            'status' => 'completed',
        ]);

        // Mock assessment attempts with high score
        $this->user->assessmentAttempts()->create([
            'assessment_id' => 1,
            'status' => 'completed',
            'score' => 85,
        ]);

        $difficulty = $method->invoke($service, $this->user);
        $this->assertEquals('advanced', $difficulty);
    }
}
