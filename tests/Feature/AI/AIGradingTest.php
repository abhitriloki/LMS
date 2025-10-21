<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\AssessmentAttempt;
use App\Models\AttemptResponse;
use App\Models\AIGradingResult;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;

class AIGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AI service responses
        $this->mockAIService();
    }

    protected function mockAIService()
    {
        $mockAIService = Mockery::mock(AIServiceInterface::class);
        
        $mockAIService->shouldReceive('analyzeText')
            ->andReturn([
                'overall_score' => 85,
                'rubric_scores' => ['content' => 8, 'grammar' => 9],
                'strengths' => ['Clear argument', 'Good structure'],
                'weaknesses' => ['Could use more examples'],
                'suggestions' => ['Add supporting evidence'],
                'confidence' => 0.85,
            ]);

        $this->app->instance(AIServiceInterface::class, $mockAIService);
    }

    public function test_essay_response_is_graded_by_ai_automatically()
    {
        // Arrange
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create([
            'passing_score' => 60,
        ]);
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
            'question_text' => 'Explain the concept of AI.',
        ]);

        $this->actingAs($user);

        // Start attempt
        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        // Submit essay response
        $response = AttemptResponse::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'response_data' => 'Artificial Intelligence is a field of computer science...',
        ]);

        // Act - Submit attempt (triggers grading)
        $response = $this->post(route('assessments.attempts.submit', $attempt));

        // Assert
        $attempt->refresh();
        $this->assertEquals('graded', $attempt->status);
        
        $aiGrading = AIGradingResult::where('attempt_response_id', $response->id)->first();
        $this->assertNotNull($aiGrading);
        $this->assertGreaterThan(0, $aiGrading->ai_score);
        $this->assertNotEmpty($aiGrading->ai_feedback);
    }

    public function test_low_confidence_grading_is_flagged_for_review()
    {
        // Arrange - Mock low confidence response
        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('analyzeText')
            ->andReturn([
                'overall_score' => 60,
                'rubric_scores' => [],
                'strengths' => [],
                'weaknesses' => ['Unclear', 'Too brief'],
                'suggestions' => ['Expand your answer'],
                'confidence' => 0.5, // Low confidence
            ]);
        $this->app->instance(AIServiceInterface::class, $mockAIService);

        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $response = AttemptResponse::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'response_data' => 'Short answer.',
        ]);

        // Act
        $this->actingAs($user)
            ->post(route('assessments.attempts.submit', $attempt));

        // Assert
        $attempt->refresh();
        $this->assertEquals('submitted', $attempt->status); // Requires manual review
        
        $aiGrading = AIGradingResult::where('attempt_response_id', $response->id)->first();
        $this->assertTrue($aiGrading->flagged_for_review);
        $this->assertNotEmpty($aiGrading->review_reason);
    }

    public function test_instructor_can_view_grading_review_queue()
    {
        // Arrange
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create();
        $assessment = Assessment::factory()->create(['created_by' => $instructor->id]);
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
        ]);
        $response = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        $gradingResult = AIGradingResult::factory()->create([
            'attempt_response_id' => $response->id,
            'flagged_for_review' => true,
            'reviewed_at' => null,
        ]);

        // Act
        $response = $this->actingAs($instructor)
            ->get(route('admin.grading.review.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee($assessment->title);
        $response->assertSee($student->name);
    }

    public function test_instructor_can_override_ai_grading()
    {
        // Arrange
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create();
        $assessment = Assessment::factory()->create(['created_by' => $instructor->id]);
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'submitted',
        ]);
        $attemptResponse = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'points_earned' => 7,
        ]);

        $gradingResult = AIGradingResult::factory()->create([
            'attempt_response_id' => $attemptResponse->id,
            'ai_score' => 7,
            'confidence_score' => 0.6,
            'flagged_for_review' => true,
        ]);

        // Act
        $response = $this->actingAs($instructor)
            ->put(route('admin.grading.review.update', $gradingResult), [
                'human_score' => 9,
                'human_feedback' => 'Good work, but could improve clarity.',
            ]);

        // Assert
        $response->assertRedirect();
        
        $gradingResult->refresh();
        $this->assertEquals(9, $gradingResult->human_score);
        $this->assertEquals('Good work, but could improve clarity.', $gradingResult->human_feedback);
        $this->assertNotNull($gradingResult->reviewed_at);
        $this->assertEquals($instructor->id, $gradingResult->reviewed_by);
        
        $attemptResponse->refresh();
        $this->assertEquals(9, $attemptResponse->points_earned);
    }

    public function test_instructor_can_accept_ai_grading_without_changes()
    {
        // Arrange
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create();
        $assessment = Assessment::factory()->create(['created_by' => $instructor->id]);
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
        ]);
        $attemptResponse = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        $gradingResult = AIGradingResult::factory()->create([
            'attempt_response_id' => $attemptResponse->id,
            'ai_score' => 8,
            'flagged_for_review' => true,
        ]);

        // Act
        $response = $this->actingAs($instructor)
            ->post(route('admin.grading.review.accept', $gradingResult));

        // Assert
        $response->assertRedirect();
        
        $gradingResult->refresh();
        $this->assertEquals(8, $gradingResult->human_score);
        $this->assertNotNull($gradingResult->reviewed_at);
        $this->assertFalse($gradingResult->flagged_for_review);
    }

    public function test_attempt_score_is_recalculated_after_override()
    {
        // Arrange
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create();
        $assessment = Assessment::factory()->create([
            'created_by' => $instructor->id,
            'passing_score' => 70,
        ]);
        
        // Create two questions
        $question1 = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'multiple_choice',
            'points' => 10,
        ]);
        $question2 = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);

        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'submitted',
            'score' => 15, // 10 from MCQ + 5 from essay
            'percentage' => 75,
        ]);

        // MCQ response (already graded)
        AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question1->id,
            'points_earned' => 10,
        ]);

        // Essay response with AI grading
        $essayResponse = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question2->id,
            'points_earned' => 5,
        ]);

        $gradingResult = AIGradingResult::factory()->create([
            'attempt_response_id' => $essayResponse->id,
            'ai_score' => 5,
            'flagged_for_review' => true,
        ]);

        // Act - Override to give full points
        $this->actingAs($instructor)
            ->put(route('admin.grading.review.update', $gradingResult), [
                'human_score' => 10,
                'human_feedback' => 'Excellent work!',
            ]);

        // Assert
        $attempt->refresh();
        $this->assertEquals(20, $attempt->score); // 10 + 10
        $this->assertEquals(100, $attempt->percentage);
        $this->assertTrue($attempt->passed);
    }

    public function test_grading_statistics_are_accurate()
    {
        // Arrange
        $instructor = User::factory()->create(['role' => 'instructor']);
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
        ]);

        // Create various grading results
        for ($i = 0; $i < 5; $i++) {
            $attempt = AssessmentAttempt::factory()->create([
                'assessment_id' => $assessment->id,
            ]);
            $response = AttemptResponse::factory()->create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ]);
            
            AIGradingResult::factory()->create([
                'attempt_response_id' => $response->id,
                'flagged_for_review' => $i < 2, // 2 flagged
                'reviewed_at' => $i === 0 ? now() : null, // 1 reviewed
                'confidence_score' => $i < 3 ? 0.6 : 0.8, // 3 low confidence
            ]);
        }

        // Act
        $response = $this->actingAs($instructor)
            ->get(route('admin.grading.statistics'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_ai_graded'] === 5
                && $stats['flagged_for_review'] === 2
                && $stats['pending_review'] === 1
                && $stats['reviewed'] === 1
                && $stats['low_confidence_count'] === 3;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
