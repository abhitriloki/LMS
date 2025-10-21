<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\AILearningPath;
use App\Models\Enrollment;
use App\Models\Department;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class LearningPathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock AI service
        $aiService = Mockery::mock(AIServiceInterface::class);
        $this->app->instance(AIServiceInterface::class, $aiService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function user_can_view_learning_paths_index()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        AILearningPath::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('learning-paths.index'));

        $response->assertStatus(200);
        $response->assertViewIs('learning-paths.index');
        $response->assertViewHas('learningPaths');
    }

    /** @test */
    public function user_can_view_create_learning_path_form()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($user)->get(route('learning-paths.create'));

        $response->assertStatus(200);
        $response->assertViewIs('learning-paths.create');
    }

    /** @test */
    public function user_can_generate_learning_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create([
            'position' => 'Junior Developer',
            'department_id' => $department->id,
        ]);

        $course = Course::factory()->create(['is_published' => true]);

        // Mock AI service response
        $aiService = $this->app->make(AIServiceInterface::class);
        $aiService->shouldReceive('generateText')
            ->once()
            ->andReturn(json_encode([
                'reasoning' => 'Test reasoning',
                'courses' => [
                    [
                        'course_id' => $course->id,
                        'order' => 1,
                        'milestone' => 'Foundation',
                        'reason' => 'Test reason',
                        'estimated_weeks' => 4,
                    ],
                ],
                'milestones' => [],
                'total_estimated_weeks' => 4,
            ]));

        $response = $this->actingAs($user)->post(route('learning-paths.store'), [
            'target_role' => 'Senior Developer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ai_learning_paths', [
            'user_id' => $user->id,
            'target_role' => 'Senior Developer',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function user_can_view_learning_path_details()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course = Course::factory()->create(['is_published' => true]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'path_data' => [
                'courses' => [
                    ['course_id' => $course->id, 'order' => 1],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('learning-paths.show', $learningPath));

        $response->assertStatus(200);
        $response->assertViewIs('learning-paths.show');
        $response->assertViewHas('learningPath');
        $response->assertViewHas('enrichedCourses');
    }

    /** @test */
    public function user_cannot_view_another_users_learning_path()
    {
        $department = Department::factory()->create();
        $user1 = User::factory()->create(['department_id' => $department->id]);
        $user2 = User::factory()->create(['department_id' => $department->id]);

        $learningPath = AILearningPath::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->get(route('learning-paths.show', $learningPath));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_start_learning_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('learning-paths.start', $learningPath));

        $response->assertRedirect(route('learning-paths.show', $learningPath));
        $response->assertSessionHas('success');

        $learningPath->refresh();
        $this->assertEquals('active', $learningPath->status);
        $this->assertNotNull($learningPath->started_at);
    }

    /** @test */
    public function user_can_pause_learning_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('learning-paths.pause', $learningPath));

        $response->assertRedirect(route('learning-paths.show', $learningPath));
        $response->assertSessionHas('success');

        $learningPath->refresh();
        $this->assertEquals('paused', $learningPath->status);
    }

    /** @test */
    public function user_can_resume_learning_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'status' => 'paused',
        ]);

        $response = $this->actingAs($user)->post(route('learning-paths.resume', $learningPath));

        $response->assertRedirect(route('learning-paths.show', $learningPath));
        $response->assertSessionHas('success');

        $learningPath->refresh();
        $this->assertEquals('active', $learningPath->status);
    }

    /** @test */
    public function user_can_optimize_learning_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course = Course::factory()->create(['is_published' => true]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'path_data' => [
                'courses' => [
                    ['course_id' => $course->id, 'order' => 1],
                ],
            ],
        ]);

        // Mock AI service response
        $aiService = $this->app->make(AIServiceInterface::class);
        $aiService->shouldReceive('generateText')
            ->once()
            ->andReturn(json_encode([
                'reasoning' => 'Optimized path',
                'courses' => [
                    [
                        'course_id' => $course->id,
                        'order' => 1,
                        'estimated_weeks' => 3,
                    ],
                ],
                'milestones' => [],
            ]));

        $response = $this->actingAs($user)->post(route('learning-paths.optimize', $learningPath));

        $response->assertRedirect(route('learning-paths.show', $learningPath));
        $response->assertSessionHas('success');

        $learningPath->refresh();
        $this->assertNotNull($learningPath->last_adjusted_at);
    }

    /** @test */
    public function user_can_adjust_learning_path_based_on_performance()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $course = Course::factory()->create(['is_published' => true]);

        $learningPath = AILearningPath::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'path_data' => [
                'courses' => [
                    ['course_id' => $course->id, 'order' => 1],
                ],
            ],
        ]);

        // Mock AI service response
        $aiService = $this->app->make(AIServiceInterface::class);
        $aiService->shouldReceive('generateText')
            ->once()
            ->andReturn(json_encode([
                'reasoning' => 'Adjusted for performance',
                'courses' => [
                    [
                        'course_id' => $course->id,
                        'order' => 1,
                        'estimated_weeks' => 4,
                    ],
                ],
                'milestones' => [],
            ]));

        $response = $this->actingAs($user)->post(route('learning-paths.adjust', $learningPath), [
            'average_score' => 85,
            'completion_rate' => 90,
        ]);

        $response->assertRedirect(route('learning-paths.show', $learningPath));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function user_can_update_learning_path_progress()
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

        $response = $this->actingAs($user)->post(route('learning-paths.update-progress', $learningPath));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $learningPath->refresh();
        $this->assertEquals(50, $learningPath->progress_percentage);
    }

    /** @test */
    public function user_can_delete_learning_path()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $learningPath = AILearningPath::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('learning-paths.destroy', $learningPath));

        $response->assertRedirect(route('learning-paths.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('ai_learning_paths', [
            'id' => $learningPath->id,
        ]);
    }

    /** @test */
    public function admin_can_view_any_users_learning_path()
    {
        $department = Department::factory()->create();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'department_id' => $department->id,
        ]);
        $user = User::factory()->create(['department_id' => $department->id]);

        $learningPath = AILearningPath::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($admin)->get(route('learning-paths.show', $learningPath));

        $response->assertStatus(200);
    }

    /** @test */
    public function validation_fails_without_target_role()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($user)->post(route('learning-paths.store'), [
            'target_role' => '',
        ]);

        $response->assertSessionHasErrors('target_role');
    }

    /** @test */
    public function guest_cannot_access_learning_paths()
    {
        $response = $this->get(route('learning-paths.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('learning-paths.create'));
        $response->assertRedirect(route('login'));
    }
}
