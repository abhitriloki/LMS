<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\AILearningPathService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Models\User;
use App\Models\Course;
use App\Models\AILearningPath;
use App\Models\Enrollment;
use App\Models\Department;
use App\Exceptions\AIServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AILearningPathServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $aiService;
    protected $learningPathService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->aiService = Mockery::mock(AIServiceInterface::class);
        $this->learningPathService = new AILearningPathService($this->aiService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_learning_path_for_user()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create([
            'position' => 'Junior Developer',
            'department_id' => $department->id,
        ]);

        $course1 = Course::factory()->create([
            'title' => 'PHP Basics',
            'is_published' => true,
            'difficulty_level' => 'beginner',
            'estimated_duration' => 4,
            'tags' => ['PHP', 'Programming'],
        ]);

        $course2 = Course::factory()->create([
            'title' => 'Advanced PHP',
            'is_published' => true,
            'difficulty_level' => 'advanced',
            'estimated_duration' => 6,
            'prerequisites' => [$course1->id],
            'tags' => ['PHP', 'Advanced'],
        ]);

        // Mock AI response
        $aiResponse = json_encode([
            'reasoning' => 'This path will help you become a Senior Developer',
            'courses' => [
                [
                    'course_id' => $course1->id,
                    'order' => 1,
                    'milestone' => 'Foundation',
                    'reason' => 'Learn PHP basics',
                    'estimated_weeks' => 4,
                ],
                [
                    'course_id' => $course2->id,
                    'order' => 2,
                    'milestone' => 'Advanced',
                    'reason' => 'Master advanced concepts',
                    'estimated_weeks' => 6,
                ],
            ],
            'milestones' => [
                [
                    'name' => 'Foundation Complete',
                    'course_ids' => [$course1->id],
                    'description' => 'Basic skills acquired',
                ],
            ],
            'total_estimated_weeks' => 10,
        ]);

        $this->aiService->shouldReceive('generateText')
            ->once()
            ->andReturn($aiResponse);

        $learningPath = $this->learningPathService->generateLearningPath($user, 'Senior Developer');

        $this->assertInstanceOf(AILearningPath::class, $learningPath);
        $this->assertEquals($user->id, $learningPath->user_id);
        $this->assertEquals('Senior Developer', $learningPath->target_role);
        $this->assertEquals('draft', $learningPath->status);
        $this->assertEquals(10, $learningPath->estimated_duration);
        $this->assertCount(2, $learningPath->path_data['courses']);
    }

    /** @test */
    public function it_validates_prerequisites_in_learning_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course1 = Course::factory()->create([
            'is_published' => true,
            'prerequisites' => [],
        ]);

        $course2 = Course::factory()->create([
            'is_published' => true,
            'prerequisites' => [$course1->id],
        ]);

        // AI response with wrong order (course2 before course1)
        $aiResponse = json_encode([
            'reasoning' => 'Test path',
            'courses' => [
                [
                    'course_id' => $course2->id,
                    'order' => 1,
                    'estimated_weeks' => 2,
                ],
                [
                    'course_id' => $course1->id,
                    'order' => 2,
                    'estimated_weeks' => 2,
                ],
            ],
            'milestones' => [],
        ]);

        $this->aiService->shouldReceive('generateText')
            ->once()
            ->andReturn($aiResponse);

        $learningPath = $this->learningPathService->generateLearningPath($user, 'Test Role');

        // Should only include course1 since course2's prerequisite isn't met
        $this->assertCount(1, $learningPath->path_data['courses']);
        $this->assertEquals($course1->id, $learningPath->path_data['courses'][0]['course_id']);
    }

    /** @test */
    public function it_optimizes_existing_learning_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course1 = Course::factory()->create(['is_published' => true]);
        $course2 = Course::factory()->create(['is_published' => true]);

        // Create completed enrollment
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course1->id,
            'status' => 'completed',
        ]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'path_data' => [
                'courses' => [
                    ['course_id' => $course1->id, 'order' => 1],
                    ['course_id' => $course2->id, 'order' => 2],
                ],
            ],
        ]);

        // Mock optimized response (removes completed course)
        $optimizedResponse = json_encode([
            'reasoning' => 'Removed completed courses',
            'courses' => [
                [
                    'course_id' => $course2->id,
                    'order' => 1,
                    'estimated_weeks' => 4,
                ],
            ],
            'milestones' => [],
        ]);

        $this->aiService->shouldReceive('generateText')
            ->once()
            ->andReturn($optimizedResponse);

        $optimized = $this->learningPathService->optimizePath($learningPath);

        $this->assertCount(1, $optimized->path_data['courses']);
        $this->assertEquals($course2->id, $optimized->path_data['courses'][0]['course_id']);
        $this->assertNotNull($optimized->last_adjusted_at);
    }

    /** @test */
    public function it_adjusts_path_based_on_performance()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course1 = Course::factory()->create(['is_published' => true]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'path_data' => [
                'courses' => [
                    ['course_id' => $course1->id, 'order' => 1],
                ],
            ],
        ]);

        $performanceData = [
            'average_score' => 45,
            'completion_rate' => 40,
            'time_spent' => 100,
            'estimated_time' => 50,
        ];

        $adjustedResponse = json_encode([
            'reasoning' => 'Added foundational courses',
            'courses' => [
                ['course_id' => $course1->id, 'order' => 1, 'estimated_weeks' => 2],
            ],
            'milestones' => [],
        ]);

        $this->aiService->shouldReceive('generateText')
            ->once()
            ->andReturn($adjustedResponse);

        $adjusted = $this->learningPathService->adjustPath($learningPath, $performanceData);

        $this->assertNotNull($adjusted->last_adjusted_at);
        $this->assertCount(1, $adjusted->adjustments);
    }

    /** @test */
    public function it_updates_progress_based_on_completed_courses()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course1 = Course::factory()->create(['is_published' => true]);
        $course2 = Course::factory()->create(['is_published' => true]);

        // Complete first course
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course1->id,
            'status' => 'completed',
        ]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'path_data' => [
                'courses' => [
                    ['course_id' => $course1->id, 'order' => 1],
                    ['course_id' => $course2->id, 'order' => 2],
                ],
            ],
            'progress_percentage' => 0,
        ]);

        $this->learningPathService->updateProgress($learningPath);

        $learningPath->refresh();
        $this->assertEquals(50, $learningPath->progress_percentage);
    }

    /** @test */
    public function it_gets_next_course_in_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course1 = Course::factory()->create(['is_published' => true]);
        $course2 = Course::factory()->create(['is_published' => true]);

        // Complete first course
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course1->id,
            'status' => 'completed',
        ]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'path_data' => [
                'courses' => [
                    ['course_id' => $course1->id, 'order' => 1],
                    ['course_id' => $course2->id, 'order' => 2],
                ],
            ],
        ]);

        $nextCourse = $this->learningPathService->getNextCourse($learningPath);

        $this->assertNotNull($nextCourse);
        $this->assertEquals($course2->id, $nextCourse['course_id']);
    }

    /** @test */
    public function it_returns_null_when_all_courses_completed()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course1 = Course::factory()->create(['is_published' => true]);

        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course1->id,
            'status' => 'completed',
        ]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'path_data' => [
                'courses' => [
                    ['course_id' => $course1->id, 'order' => 1],
                ],
            ],
        ]);

        $nextCourse = $this->learningPathService->getNextCourse($learningPath);

        $this->assertNull($nextCourse);
    }

    /** @test */
    public function it_throws_exception_on_ai_service_failure()
    {
        $this->expectException(AIServiceException::class);

        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $this->aiService->shouldReceive('generateText')
            ->once()
            ->andThrow(new AIServiceException('AI service unavailable'));

        $this->learningPathService->generateLearningPath($user, 'Test Role');
    }

    /** @test */
    public function it_throws_exception_on_invalid_ai_response()
    {
        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Failed to parse AI response');

        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $this->aiService->shouldReceive('generateText')
            ->once()
            ->andReturn('Invalid JSON response');

        $this->learningPathService->generateLearningPath($user, 'Test Role');
    }
}
