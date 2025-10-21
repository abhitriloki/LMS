<?php

namespace Tests\Unit\Services\AI;

use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Course;
use App\Models\AIQuestionJob;
use App\Models\GeneratedQuestion;
use App\Services\AI\AIQuestionGeneratorService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class AIQuestionGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AIQuestionGeneratorService $service;
    protected $mockAIService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AI service
        $this->mockAIService = Mockery::mock(AIServiceInterface::class);
        $this->service = new AIQuestionGeneratorService($this->mockAIService);

        Storage::fake('local');
    }

    public function test_generates_questions_from_text_lesson()
    {
        $lesson = $this->createTextLesson();

        $mockResponse = json_encode([
            [
                'question_text' => 'What is Laravel?',
                'question_type' => 'multiple_choice',
                'options' => ['A PHP framework', 'A database', 'A server', 'A language'],
                'correct_answer' => 0,
                'explanation' => 'Laravel is a PHP web application framework.',
                'difficulty' => 'easy'
            ]
        ]);

        $this->mockAIService
            ->shouldReceive('generateText')
            ->once()
            ->andReturn($mockResponse);

        $parameters = [
            'count' => 1,
            'types' => ['multiple_choice'],
            'difficulty' => 'easy',
            'include_explanation' => true,
            'points_per_question' => 1,
        ];

        $questions = $this->service->generateQuestionsFromLesson($lesson, $parameters);

        $this->assertCount(1, $questions);
        $this->assertEquals('What is Laravel?', $questions[0]['question_text']);
        $this->assertEquals('multiple_choice', $questions[0]['question_type']);
        $this->assertCount(4, $questions[0]['options']);
    }

    public function test_validates_parameters()
    {
        $lesson = $this->createTextLesson();

        $mockResponse = json_encode([
            [
                'question_text' => 'Test question?',
                'question_type' => 'true_false',
                'options' => ['True', 'False'],
                'correct_answer' => 'true',
                'explanation' => 'Test explanation'
            ]
        ]);

        $this->mockAIService
            ->shouldReceive('generateText')
            ->once()
            ->andReturn($mockResponse);

        // Test with invalid parameters
        $parameters = [
            'count' => 100, // Should be capped at 20
            'types' => ['invalid_type'], // Should fallback to multiple_choice
            'difficulty' => 'invalid', // Should fallback to medium
            'points_per_question' => 100, // Should be capped at 10
        ];

        $questions = $this->service->generateQuestionsFromLesson($lesson, $parameters);

        // Should still generate questions with validated parameters
        $this->assertIsArray($questions);
    }

    public function test_extracts_text_from_text_lesson()
    {
        $lesson = $this->createTextLesson();

        $text = $this->service->extractContentText($lesson);

        $this->assertStringContainsString('Test Lesson', $text);
        $this->assertStringContainsString('This is test content', $text);
    }

    public function test_throws_exception_when_no_content_extracted()
    {
        $lesson = CourseLesson::factory()->create([
            'content_type' => 'text',
            'content_path' => null,
            'title' => '',
            'description' => null,
        ]);

        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('No content text could be extracted');

        $this->service->generateQuestionsFromLesson($lesson, [
            'count' => 1,
            'types' => ['multiple_choice'],
        ]);
    }

    public function test_formats_multiple_choice_question()
    {
        $lesson = $this->createTextLesson();

        $mockResponse = json_encode([
            [
                'question_text' => 'What is 2+2?',
                'question_type' => 'multiple_choice',
                'options' => ['3', '4', '5', '6'],
                'correct_answer' => 1,
                'explanation' => '2+2 equals 4'
            ]
        ]);

        $this->mockAIService
            ->shouldReceive('generateText')
            ->once()
            ->andReturn($mockResponse);

        $questions = $this->service->generateQuestionsFromLesson($lesson, [
            'count' => 1,
            'types' => ['multiple_choice'],
            'points_per_question' => 2,
        ]);

        $this->assertEquals('multiple_choice', $questions[0]['question_type']);
        $this->assertCount(4, $questions[0]['options']);
        $this->assertEquals([1], $questions[0]['correct_answer']);
        $this->assertEquals(2, $questions[0]['points']);
    }

    public function test_formats_true_false_question()
    {
        $lesson = $this->createTextLesson();

        $mockResponse = json_encode([
            [
                'question_text' => 'Laravel is a PHP framework.',
                'question_type' => 'true_false',
                'options' => ['True', 'False'],
                'correct_answer' => 'true',
                'explanation' => 'Yes, Laravel is indeed a PHP framework.'
            ]
        ]);

        $this->mockAIService
            ->shouldReceive('generateText')
            ->once()
            ->andReturn($mockResponse);

        $questions = $this->service->generateQuestionsFromLesson($lesson, [
            'count' => 1,
            'types' => ['true_false'],
        ]);

        $this->assertEquals('true_false', $questions[0]['question_type']);
        $this->assertEquals(['True', 'False'], $questions[0]['options']);
        $this->assertEquals([0], $questions[0]['correct_answer']); // True is index 0
    }

    public function test_formats_fill_in_blank_question()
    {
        $lesson = $this->createTextLesson();

        $mockResponse = json_encode([
            [
                'question_text' => 'Laravel uses the [BLANK] pattern.',
                'question_type' => 'fill_in_blank',
                'correct_answer' => 'MVC',
                'explanation' => 'Laravel follows the Model-View-Controller pattern.'
            ]
        ]);

        $this->mockAIService
            ->shouldReceive('generateText')
            ->once()
            ->andReturn($mockResponse);

        $questions = $this->service->generateQuestionsFromLesson($lesson, [
            'count' => 1,
            'types' => ['fill_in_blank'],
        ]);

        $this->assertEquals('fill_in_blank', $questions[0]['question_type']);
        $this->assertNull($questions[0]['options']);
        $this->assertEquals(['MVC'], $questions[0]['correct_answer']);
    }

    public function test_formats_essay_question()
    {
        $lesson = $this->createTextLesson();

        $mockResponse = json_encode([
            [
                'question_text' => 'Explain the benefits of using Laravel.',
                'question_type' => 'essay',
                'explanation' => 'Look for mentions of MVC, Eloquent ORM, routing, etc.'
            ]
        ]);

        $this->mockAIService
            ->shouldReceive('generateText')
            ->once()
            ->andReturn($mockResponse);

        $questions = $this->service->generateQuestionsFromLesson($lesson, [
            'count' => 1,
            'types' => ['essay'],
        ]);

        $this->assertEquals('essay', $questions[0]['question_type']);
        $this->assertNull($questions[0]['options']);
        $this->assertNull($questions[0]['correct_answer']);
    }

    public function test_saves_generated_questions_to_database()
    {
        $job = AIQuestionJob::factory()->create();

        $questions = [
            [
                'question_text' => 'Test question 1?',
                'question_type' => 'multiple_choice',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => [0],
                'explanation' => 'Test explanation',
                'points' => 1,
            ],
            [
                'question_text' => 'Test question 2?',
                'question_type' => 'true_false',
                'options' => ['True', 'False'],
                'correct_answer' => [1],
                'explanation' => 'Test explanation 2',
                'points' => 1,
            ],
        ];

        $count = $this->service->saveGeneratedQuestions($job, $questions);

        $this->assertEquals(2, $count);
        $this->assertDatabaseCount('generated_questions', 2);
        
        $savedQuestion = GeneratedQuestion::first();
        $this->assertEquals('Test question 1?', $savedQuestion->question_text);
        $this->assertEquals('pending', $savedQuestion->status);
    }

    public function test_handles_invalid_json_response()
    {
        $lesson = $this->createTextLesson();

        $this->mockAIService
            ->shouldReceive('generateText')
            ->once()
            ->andReturn('Invalid JSON response');

        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Failed to parse AI response');

        $this->service->generateQuestionsFromLesson($lesson, [
            'count' => 1,
            'types' => ['multiple_choice'],
        ]);
    }

    public function test_gets_supported_types()
    {
        $types = $this->service->getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains('multiple_choice', $types);
        $this->assertContains('true_false', $types);
        $this->assertContains('fill_in_blank', $types);
        $this->assertContains('essay', $types);
    }

    public function test_gets_difficulty_levels()
    {
        $levels = $this->service->getDifficultyLevels();

        $this->assertIsArray($levels);
        $this->assertContains('easy', $levels);
        $this->assertContains('medium', $levels);
        $this->assertContains('hard', $levels);
    }

    protected function createTextLesson(): CourseLesson
    {
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);

        return CourseLesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Test Lesson',
            'description' => 'Test description',
            'content_type' => 'text',
            'content_path' => 'This is test content about Laravel framework.',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
