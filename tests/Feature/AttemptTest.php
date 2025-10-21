<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttemptTest extends TestCase
{
    use RefreshDatabase;

    protected AttemptService $attemptService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attemptService = app(AttemptService::class);
    }

    public function test_can_start_attempt()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);

        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $attempt = $this->attemptService->startAttempt($assessment, $user);

        $this->assertInstanceOf(AssessmentAttempt::class, $attempt);
        $this->assertEquals('in_progress', $attempt->status);
        $this->assertEquals($user->id, $attempt->user_id);
        $this->assertEquals($assessment->id, $attempt->assessment_id);
    }

    public function test_cannot_start_attempt_when_max_attempts_reached()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'max_attempts' => 1,
        ]);

        $user->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        // Create one attempt
        $assessment->attempts()->create([
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'completed',
        ]);

        $this->expectException(\Exception::class);
        $this->attemptService->startAttempt($assessment, $user);
    }

    public function test_can_save_response()
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = $assessment->questions()->create([
            'question_text' => 'Test Question',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order_index' => 0,
        ]);

        $option = $question->options()->create([
            'option_text' => 'Answer',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $attempt = $assessment->attempts()->create([
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $response = $this->attemptService->saveResponse($attempt, $question, $option->id);

        $this->assertDatabaseHas('attempt_responses', [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_can_submit_attempt()
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create(['passing_score' => 70]);
        
        $question = $assessment->questions()->create([
            'question_text' => 'Test Question',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order_index' => 0,
        ]);

        $correctOption = $question->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $attempt = $assessment->attempts()->create([
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        // Save response
        $attempt->responses()->create([
            'question_id' => $question->id,
            'response_data' => $correctOption->id,
        ]);

        $submitted = $this->attemptService->submitAttempt($attempt);

        $this->assertEquals('graded', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertTrue($submitted->passed);
    }

    public function test_grading_calculates_correct_score()
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create(['passing_score' => 50]);
        
        // Create 2 questions worth 5 points each
        $q1 = $assessment->questions()->create([
            'question_text' => 'Question 1',
            'question_type' => 'multiple_choice',
            'points' => 5,
            'order_index' => 0,
        ]);

        $q1CorrectOption = $q1->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $q2 = $assessment->questions()->create([
            'question_text' => 'Question 2',
            'question_type' => 'multiple_choice',
            'points' => 5,
            'order_index' => 1,
        ]);

        $q2WrongOption = $q2->options()->create([
            'option_text' => 'Wrong',
            'is_correct' => false,
            'order_index' => 0,
        ]);

        $q2->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'order_index' => 1,
        ]);

        $attempt = $assessment->attempts()->create([
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        // Answer first correctly, second incorrectly
        $attempt->responses()->create([
            'question_id' => $q1->id,
            'response_data' => $q1CorrectOption->id,
        ]);

        $attempt->responses()->create([
            'question_id' => $q2->id,
            'response_data' => $q2WrongOption->id,
        ]);

        $graded = $this->attemptService->gradeAttempt($attempt);

        $this->assertEquals(5, $graded->score); // Only first question correct
        $this->assertEquals(50, $graded->percentage); // 5/10 = 50%
        $this->assertTrue($graded->passed); // 50% meets passing score
    }

    public function test_can_manually_grade_response()
    {
        $user = User::factory()->create();
        $grader = User::factory()->create(['role' => 'instructor']);
        $assessment = Assessment::factory()->create();
        
        $question = $assessment->questions()->create([
            'question_text' => 'Essay Question',
            'question_type' => 'essay',
            'points' => 10,
            'order_index' => 0,
        ]);

        $attempt = $assessment->attempts()->create([
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'submitted',
        ]);

        $response = $attempt->responses()->create([
            'question_id' => $question->id,
            'response_data' => 'Student essay response',
        ]);

        $graded = $this->attemptService->manuallyGradeResponse(
            $response,
            8,
            'Good work!',
            $grader
        );

        $this->assertEquals(8, $graded->points_earned);
        $this->assertEquals('Good work!', $graded->feedback);
    }

    public function test_can_get_attempt_results()
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create([
            'show_results' => true,
            'show_correct_answers' => true,
        ]);
        
        $question = $assessment->questions()->create([
            'question_text' => 'Test Question',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order_index' => 0,
        ]);

        $correctOption = $question->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $attempt = $assessment->attempts()->create([
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'graded',
            'score' => 10,
            'percentage' => 100,
            'passed' => true,
            'submitted_at' => now(),
            'time_taken' => 300,
        ]);

        $attempt->responses()->create([
            'question_id' => $question->id,
            'response_data' => $correctOption->id,
            'is_correct' => true,
            'points_earned' => 10,
        ]);

        $results = $this->attemptService->getAttemptResults($attempt);

        $this->assertIsArray($results);
        $this->assertEquals(10, $results['score']);
        $this->assertEquals(100, $results['percentage']);
        $this->assertTrue($results['passed']);
        $this->assertTrue($results['show_correct_answers']);
        $this->assertArrayHasKey('responses', $results);
    }

    public function test_auto_submit_on_time_limit_exceeded()
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create([
            'time_limit' => 1, // 1 minute
        ]);

        $attempt = $assessment->attempts()->create([
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(2), // Started 2 minutes ago
            'status' => 'in_progress',
        ]);

        $this->assertTrue($attempt->isTimeLimitExceeded());

        $submitted = $this->attemptService->autoSubmitAttempt($attempt);

        $this->assertNotEquals('in_progress', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
    }
}
