<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\AIGradingService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Models\Question;
use App\Models\AttemptResponse;
use App\Models\AssessmentAttempt;
use App\Models\Assessment;
use App\Models\User;
use App\Models\AIGradingResult;
use App\Exceptions\AIServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AIGradingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AIGradingService $service;
    protected $mockAIService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAIService = Mockery::mock(AIServiceInterface::class);
        $this->service = new AIGradingService($this->mockAIService);
    }

    public function test_grades_essay_response_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
        $response = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'response_data' => 'This is a student essay response.',
        ]);

        $mockAnalysis = [
            'overall_score' => 85,
            'rubric_scores' => ['content' => 8, 'grammar' => 9],
            'strengths' => ['Clear argument', 'Good structure'],
            'weaknesses' => ['Could use more examples'],
            'suggestions' => ['Add supporting evidence'],
            'confidence' => 0.85,
        ];

        $this->mockAIService
            ->shouldReceive('analyzeText')
            ->once()
            ->andReturn($mockAnalysis);

        // Act
        $result = $this->service->gradeEssay($response);

        // Assert
        $this->assertInstanceOf(AIGradingResult::class, $result);
        $this->assertEquals(8.5, $result->ai_score); // 85% of 10 points
        $this->assertEquals(0.85, $result->confidence_score);
        $this->assertFalse($result->flagged_for_review);
        $this->assertNotEmpty($result->ai_feedback);
    }

    public function test_flags_low_confidence_responses_for_review()
    {
        // Arrange
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
        $response = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'response_data' => 'Short response.',
        ]);

        $mockAnalysis = [
            'overall_score' => 60,
            'rubric_scores' => [],
            'strengths' => [],
            'weaknesses' => ['Too brief', 'Lacks detail'],
            'suggestions' => ['Expand your answer'],
            'confidence' => 0.5, // Low confidence
        ];

        $this->mockAIService
            ->shouldReceive('analyzeText')
            ->once()
            ->andReturn($mockAnalysis);

        // Act
        $result = $this->service->gradeEssay($response);

        // Assert
        $this->assertTrue($result->flagged_for_review);
        $this->assertEquals('Low confidence score', $result->review_reason);
        $this->assertEquals(0.5, $result->confidence_score);
    }

    public function test_handles_ai_service_failure_gracefully()
    {
        // Arrange
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
        $response = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'response_data' => 'Essay response.',
        ]);

        $this->mockAIService
            ->shouldReceive('analyzeText')
            ->once()
            ->andThrow(new AIServiceException('API error'));

        // Act
        $result = $this->service->gradeEssay($response);

        // Assert
        $this->assertTrue($result->flagged_for_review);
        $this->assertEquals(0, $result->ai_score);
        $this->assertEquals(0, $result->confidence_score);
        $this->assertStringContainsString('AI service error', $result->review_reason);
    }

    public function test_throws_exception_for_non_essay_questions()
    {
        // Arrange
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'multiple_choice',
            'points' => 5,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
        $response = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'response_data' => [1],
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->service->gradeEssay($response);
    }

    public function test_calculates_confidence_score_correctly()
    {
        // Arrange
        $analysis = [
            'confidence' => 0.8,
            'strengths' => ['Good', 'Clear', 'Detailed'],
            'weaknesses' => ['Minor issue'],
        ];

        // Act
        $confidence = $this->service->calculateConfidence($analysis);

        // Assert
        $this->assertGreaterThanOrEqual(0, $confidence);
        $this->assertLessThanOrEqual(1, $confidence);
        $this->assertEquals(0.85, $confidence); // 0.8 + 0.05 for strengths
    }

    public function test_calculates_confidence_with_many_weaknesses()
    {
        // Arrange
        $analysis = [
            'confidence' => 0.7,
            'strengths' => [],
            'weaknesses' => ['Issue 1', 'Issue 2', 'Issue 3', 'Issue 4'],
        ];

        // Act
        $confidence = $this->service->calculateConfidence($analysis);

        // Assert
        $this->assertEquals(0.6, $confidence); // 0.7 - 0.1 for many weaknesses
    }

    public function test_generates_appropriate_feedback()
    {
        // Arrange
        $analysis = [
            'overall_score' => 90,
            'strengths' => ['Clear thesis', 'Good examples'],
            'weaknesses' => ['Minor grammar issues'],
            'suggestions' => ['Proofread carefully'],
        ];

        // Act
        $feedback = $this->service->generateFeedback($analysis, 'Sample response');

        // Assert
        $this->assertStringContainsString('Excellent work', $feedback);
        $this->assertStringContainsString('Clear thesis', $feedback);
        $this->assertStringContainsString('Minor grammar issues', $feedback);
        $this->assertStringContainsString('Proofread carefully', $feedback);
    }

    public function test_calculates_similarity_between_texts()
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateSimilarity');
        $method->setAccessible(true);

        // Test identical texts
        $similarity1 = $method->invoke($this->service, 'Hello world', 'Hello world');
        $this->assertEquals(1.0, $similarity1);

        // Test completely different texts
        $similarity2 = $method->invoke($this->service, 'Hello', 'Goodbye');
        $this->assertLessThan(0.5, $similarity2);

        // Test similar texts
        $similarity3 = $method->invoke($this->service, 'Hello world', 'Hello there');
        $this->assertGreaterThan(0.5, $similarity3);
        $this->assertLessThan(1.0, $similarity3);
    }

    public function test_batch_grades_multiple_essays()
    {
        // Arrange
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);

        $responses = AttemptResponse::factory()->count(3)->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        $mockAnalysis = [
            'overall_score' => 80,
            'confidence' => 0.8,
            'strengths' => [],
            'weaknesses' => [],
            'suggestions' => [],
        ];

        $this->mockAIService
            ->shouldReceive('analyzeText')
            ->times(3)
            ->andReturn($mockAnalysis);

        // Act
        $results = $this->service->batchGradeEssays($responses->all());

        // Assert
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(AIGradingResult::class, $result);
        }
    }

    public function test_gets_flagged_responses()
    {
        // Arrange
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
        $response = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        AIGradingResult::factory()->create([
            'attempt_response_id' => $response->id,
            'flagged_for_review' => true,
            'reviewed_at' => null,
        ]);

        AIGradingResult::factory()->create([
            'attempt_response_id' => $response->id,
            'flagged_for_review' => false,
        ]);

        // Act
        $flagged = $this->service->getFlaggedResponses();

        // Assert
        $this->assertCount(1, $flagged);
        $this->assertTrue($flagged->first()->flagged_for_review);
    }

    public function test_sets_low_confidence_threshold()
    {
        // Act
        $this->service->setLowConfidenceThreshold(0.6);

        // Assert - test by grading with confidence just above threshold
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);
        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
        $response = AttemptResponse::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'response_data' => 'Test response',
        ]);

        $mockAnalysis = [
            'overall_score' => 75,
            'confidence' => 0.65, // Above new threshold
            'strengths' => [],
            'weaknesses' => [],
            'suggestions' => [],
        ];

        $this->mockAIService
            ->shouldReceive('analyzeText')
            ->once()
            ->andReturn($mockAnalysis);

        $result = $this->service->gradeEssay($response);

        $this->assertFalse($result->flagged_for_review);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
