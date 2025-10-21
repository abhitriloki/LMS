<?php

namespace Tests\Feature\Integration;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\AttemptService;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTakingTest extends TestCase
{
    use RefreshDatabase;

    protected AssessmentService $assessmentService;
    protected AttemptService $attemptService;
    protected EnrollmentService $enrollmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessmentService = app(AssessmentService::class);
        $this->attemptService = app(AttemptService::class);
        $this->enrollmentService = app(EnrollmentService::class);
    }

    /**
     * Test complete assessment taking flow
     */
    public function test_user_can_complete_full_assessment_flow()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = $this->createAssessmentWithQuestions($course);

        // Enroll user in course
        $this->enrollmentService->enroll($user, $course);

        // View assessment start page
        $response = $this->actingAs($user)->get(route('assessments.start', $assessment));
        $response->assertStatus(200);
        $response->assertSee($assessment->title);
        $response->assertSee('Start Assessment');

        // Start assessment
        $response = $this->actingAs($user)->post(route('assessments.start', $assessment));
        $response->assertRedirect();

        $attempt = $user->assessmentAttempts()
            ->where('assessment_id', $assessment->id)
            ->where('status', 'in_progress')
            ->first();

        $this->assertNotNull($attempt);
        $this->assertNotNull($attempt->started_at);

        // Take assessment - view questions
        $response = $this->actingAs($user)->get(route('attempts.take', $attempt));
        $response->assertStatus(200);

        foreach ($assessment->questions as $question) {
            $response->assertSee($question->question_text);
        }

        // Submit answers
        $responses = [];
        foreach ($assessment->questions as $question) {
            $correctOption = $question->options()->where('is_correct', true)->first();
            $responses[$question->id] = $correctOption->id;
        }

        $response = $this->actingAs($user)->post(route('attempts.submit', $attempt), [
            'responses' => $responses,
        ]);

        $response->assertRedirect(route('attempts.results', $attempt));

        // Check attempt is completed
        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);
        $this->assertNotNull($attempt->completed_at);
        $this->assertEquals(100, $attempt->score); // All correct answers

        // View results
        $response = $this->actingAs($user)->get(route('attempts.results', $attempt));
        $response->assertStatus(200);
        $response->assertSee('Passed');
        $response->assertSee($attempt->score);
    }

    /**
     * Test assessment with time limit
     */
    public function test_assessment_with_time_limit_auto_submits()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'time_limit' => 30, // 30 minutes
        ]);

        $this->enrollmentService->enroll($user, $course);

        // Start assessment
        $attempt = $this->attemptService->startAttempt($assessment, $user);

        // Simulate time expiry by setting started_at to past
        $attempt->update([
            'started_at' => now()->subMinutes(31),
        ]);

        // Try to access assessment after time expired
        $response = $this->actingAs($user)->get(route('attempts.take', $attempt));
        $response->assertRedirect();

        // Attempt should be auto-submitted
        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);
    }

    /**
     * Test assessment with max attempts limit
     */
    public function test_user_cannot_exceed_max_attempts()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'max_attempts' => 2,
        ]);

        $this->enrollmentService->enroll($user, $course);

        // First attempt
        $attempt1 = $this->attemptService->startAttempt($assessment, $user);
        $this->attemptService->submitAttempt($attempt1, []);
        $this->assertEquals('completed', $attempt1->fresh()->status);

        // Second attempt
        $attempt2 = $this->attemptService->startAttempt($assessment, $user);
        $this->attemptService->submitAttempt($attempt2, []);
        $this->assertEquals('completed', $attempt2->fresh()->status);

        // Third attempt should fail
        $canAttempt = $this->assessmentService->canUserAttemptAssessment($assessment, $user);
        $this->assertFalse($canAttempt);

        try {
            $this->attemptService->startAttempt($assessment, $user);
            $this->fail('Should not be able to start third attempt');
        } catch (\Exception $e) {
            $this->assertStringContainsString('maximum', strtolower($e->getMessage()));
        }
    }

    /**
     * Test assessment with randomized questions
     */
    public function test_assessment_with_randomized_questions()
    {
        $user1 = User::factory()->create(['role' => 'employee']);
        $user2 = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'randomize_questions' => true,
        ]);

        // Create multiple questions
        for ($i = 0; $i < 10; $i++) {
            Question::factory()->create([
                'assessment_id' => $assessment->id,
                'question_type' => 'multiple_choice',
            ]);
        }

        $this->enrollmentService->enroll($user1, $course);
        $this->enrollmentService->enroll($user2, $course);

        // Start attempts for both users
        $attempt1 = $this->attemptService->startAttempt($assessment, $user1);
        $attempt2 = $this->attemptService->startAttempt($assessment, $user2);

        // Get questions for each attempt
        $questions1 = $this->attemptService->getQuestionsForAttempt($attempt1);
        $questions2 = $this->attemptService->getQuestionsForAttempt($attempt2);

        // Questions should be in different order (with high probability)
        $order1 = $questions1->pluck('id')->toArray();
        $order2 = $questions2->pluck('id')->toArray();

        // At least one position should be different
        $this->assertNotEquals($order1, $order2);
    }

    /**
     * Test different question types
     */
    public function test_assessment_with_multiple_question_types()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
        ]);

        $this->enrollmentService->enroll($user, $course);

        // Multiple choice question
        $mcq = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'What is 2+2?',
            'points' => 10,
        ]);

        $mcq->options()->create(['option_text' => '4', 'is_correct' => true, 'order_index' => 0]);
        $mcq->options()->create(['option_text' => '5', 'is_correct' => false, 'order_index' => 1]);

        // True/False question
        $tfq = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'true_false',
            'question_text' => 'The sky is blue.',
            'points' => 10,
        ]);

        $tfq->options()->create(['option_text' => 'True', 'is_correct' => true, 'order_index' => 0]);
        $tfq->options()->create(['option_text' => 'False', 'is_correct' => false, 'order_index' => 1]);

        // Fill in the blank
        $fibq = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'fill_in_blank',
            'question_text' => 'The capital of France is ____.',
            'correct_answer' => 'Paris',
            'points' => 10,
        ]);

        // Essay question
        $essayq = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'question_text' => 'Explain object-oriented programming.',
            'points' => 20,
        ]);

        // Start and complete assessment
        $attempt = $this->attemptService->startAttempt($assessment, $user);

        $responses = [
            $mcq->id => $mcq->options()->where('is_correct', true)->first()->id,
            $tfq->id => $tfq->options()->where('is_correct', true)->first()->id,
            $fibq->id => 'Paris',
            $essayq->id => 'OOP is a programming paradigm based on objects...',
        ];

        $this->attemptService->submitAttempt($attempt, $responses);

        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);

        // Check responses were saved
        $this->assertEquals(4, $attempt->responses()->count());
    }

    /**
     * Test assessment review functionality
     */
    public function test_user_can_review_completed_assessment()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'allow_review' => true,
            'show_correct_answers' => true,
        ]);

        $this->enrollmentService->enroll($user, $course);

        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'multiple_choice',
            'points' => 10,
        ]);

        $correctOption = $question->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $wrongOption = $question->options()->create([
            'option_text' => 'Wrong',
            'is_correct' => false,
            'order_index' => 1,
        ]);

        // Complete assessment with wrong answer
        $attempt = $this->attemptService->startAttempt($assessment, $user);
        $this->attemptService->submitAttempt($attempt, [
            $question->id => $wrongOption->id,
        ]);

        // Review assessment
        $response = $this->actingAs($user)->get(route('attempts.review', $attempt));
        $response->assertStatus(200);
        $response->assertSee($question->question_text);
        $response->assertSee('Correct Answer');
        $response->assertSee($correctOption->option_text);
    }

    /**
     * Test assessment without review allowed
     */
    public function test_user_cannot_review_when_not_allowed()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'allow_review' => false,
        ]);

        $this->enrollmentService->enroll($user, $course);

        $attempt = $this->attemptService->startAttempt($assessment, $user);
        $this->attemptService->submitAttempt($attempt, []);

        // Try to review
        $response = $this->actingAs($user)->get(route('attempts.review', $attempt));
        $response->assertStatus(403);
    }

    /**
     * Test passing score requirement
     */
    public function test_assessment_pass_fail_based_on_passing_score()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'passing_score' => 70,
        ]);

        $this->enrollmentService->enroll($user, $course);

        // Create 10 questions worth 10 points each
        for ($i = 0; $i < 10; $i++) {
            $question = Question::factory()->create([
                'assessment_id' => $assessment->id,
                'question_type' => 'multiple_choice',
                'points' => 10,
            ]);

            $question->options()->create([
                'option_text' => 'Correct',
                'is_correct' => true,
                'order_index' => 0,
            ]);

            $question->options()->create([
                'option_text' => 'Wrong',
                'is_correct' => false,
                'order_index' => 1,
            ]);
        }

        // Attempt 1: Answer 6 correctly (60% - fail)
        $attempt1 = $this->attemptService->startAttempt($assessment, $user);
        $responses1 = [];

        foreach ($assessment->questions()->take(6)->get() as $question) {
            $responses1[$question->id] = $question->options()->where('is_correct', true)->first()->id;
        }

        foreach ($assessment->questions()->skip(6)->get() as $question) {
            $responses1[$question->id] = $question->options()->where('is_correct', false)->first()->id;
        }

        $this->attemptService->submitAttempt($attempt1, $responses1);
        $attempt1->refresh();

        $this->assertEquals(60, $attempt1->score);
        $this->assertFalse($attempt1->passed);

        // Attempt 2: Answer 8 correctly (80% - pass)
        $attempt2 = $this->attemptService->startAttempt($assessment, $user);
        $responses2 = [];

        foreach ($assessment->questions()->take(8)->get() as $question) {
            $responses2[$question->id] = $question->options()->where('is_correct', true)->first()->id;
        }

        foreach ($assessment->questions()->skip(8)->get() as $question) {
            $responses2[$question->id] = $question->options()->where('is_correct', false)->first()->id;
        }

        $this->attemptService->submitAttempt($attempt2, $responses2);
        $attempt2->refresh();

        $this->assertEquals(80, $attempt2->score);
        $this->assertTrue($attempt2->passed);
    }

    /**
     * Helper method to create assessment with questions
     */
    protected function createAssessmentWithQuestions(Course $course): Assessment
    {
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'passing_score' => 70,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $question = Question::factory()->create([
                'assessment_id' => $assessment->id,
                'question_type' => 'multiple_choice',
                'points' => 20,
            ]);

            $question->options()->create([
                'option_text' => 'Correct Answer',
                'is_correct' => true,
                'order_index' => 0,
            ]);

            $question->options()->create([
                'option_text' => 'Wrong Answer',
                'is_correct' => false,
                'order_index' => 1,
            ]);
        }

        return $assessment->fresh(['questions.options']);
    }
}
