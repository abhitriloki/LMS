<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected AssessmentService $assessmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessmentService = app(AssessmentService::class);
    }

    public function test_can_create_assessment()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create();

        $data = [
            'course_id' => $course->id,
            'title' => 'Test Assessment',
            'description' => 'Test Description',
            'passing_score' => 70,
            'time_limit' => 60,
            'max_attempts' => 3,
        ];

        $assessment = $this->assessmentService->createAssessment($data, $user);

        $this->assertInstanceOf(Assessment::class, $assessment);
        $this->assertEquals('Test Assessment', $assessment->title);
        $this->assertEquals($user->id, $assessment->created_by);
        $this->assertEquals(70, $assessment->passing_score);
    }

    public function test_can_update_assessment()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $assessment = Assessment::factory()->create(['created_by' => $user->id]);

        $data = [
            'title' => 'Updated Title',
            'passing_score' => 80,
        ];

        $updated = $this->assessmentService->updateAssessment($assessment, $data);

        $this->assertEquals('Updated Title', $updated->title);
        $this->assertEquals(80, $updated->passing_score);
    }

    public function test_cannot_publish_assessment_without_questions()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $assessment = Assessment::factory()->create([
            'created_by' => $user->id,
            'is_published' => false,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot publish assessment without questions');

        $this->assessmentService->publishAssessment($assessment);
    }

    public function test_can_clone_assessment()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $assessment = Assessment::factory()->create(['created_by' => $user->id]);
        
        // Add a question
        $question = $assessment->questions()->create([
            'question_text' => 'Test Question',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order_index' => 0,
        ]);

        $question->options()->create([
            'option_text' => 'Option 1',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $cloned = $this->assessmentService->cloneAssessment($assessment, $user);

        $this->assertNotEquals($assessment->id, $cloned->id);
        $this->assertStringContainsString('(Copy)', $cloned->title);
        $this->assertEquals($assessment->questions()->count(), $cloned->questions()->count());
        $this->assertFalse($cloned->is_published);
    }

    public function test_can_get_assessment_statistics()
    {
        $assessment = Assessment::factory()->create();
        
        $statistics = $this->assessmentService->getAssessmentStatistics($assessment);

        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('total_attempts', $statistics);
        $this->assertArrayHasKey('average_score', $statistics);
        $this->assertArrayHasKey('pass_rate', $statistics);
    }

    public function test_user_can_access_published_assessment_when_enrolled()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);

        // Enroll user
        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $canAccess = $this->assessmentService->canUserAccessAssessment($assessment, $user);

        $this->assertTrue($canAccess);
    }

    public function test_user_cannot_access_unpublished_assessment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => false,
        ]);

        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $canAccess = $this->assessmentService->canUserAccessAssessment($assessment, $user);

        $this->assertFalse($canAccess);
    }

    public function test_user_cannot_attempt_when_max_attempts_reached()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'max_attempts' => 2,
        ]);

        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        // Create 2 attempts
        $assessment->attempts()->create(['user_id' => $user->id, 'started_at' => now(), 'status' => 'completed']);
        $assessment->attempts()->create(['user_id' => $user->id, 'started_at' => now(), 'status' => 'completed']);

        $canAttempt = $this->assessmentService->canUserAttemptAssessment($assessment, $user);

        $this->assertFalse($canAttempt);
    }
}
