<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Question;
use App\Services\QuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    protected QuestionService $questionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->questionService = app(QuestionService::class);
    }

    public function test_can_create_question_with_options()
    {
        $assessment = Assessment::factory()->create();

        $data = [
            'question_text' => 'What is 2 + 2?',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'options' => [
                ['option_text' => '3', 'is_correct' => false],
                ['option_text' => '4', 'is_correct' => true],
                ['option_text' => '5', 'is_correct' => false],
            ],
        ];

        $question = $this->questionService->createQuestion($assessment, $data);

        $this->assertInstanceOf(Question::class, $question);
        $this->assertEquals('What is 2 + 2?', $question->question_text);
        $this->assertEquals(3, $question->options()->count());
        $this->assertEquals(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_can_update_question()
    {
        $assessment = Assessment::factory()->create();
        $question = $assessment->questions()->create([
            'question_text' => 'Original Question',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order_index' => 0,
        ]);

        $data = [
            'question_text' => 'Updated Question',
            'points' => 2,
        ];

        $updated = $this->questionService->updateQuestion($question, $data);

        $this->assertEquals('Updated Question', $updated->question_text);
        $this->assertEquals(2, $updated->points);
    }

    public function test_can_delete_question()
    {
        $assessment = Assessment::factory()->create();
        $question = $assessment->questions()->create([
            'question_text' => 'Test Question',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order_index' => 0,
        ]);

        $questionId = $question->id;

        $this->questionService->deleteQuestion($question);

        $this->assertDatabaseMissing('questions', ['id' => $questionId]);
    }

    public function test_can_reorder_questions()
    {
        $assessment = Assessment::factory()->create();
        
        $q1 = $assessment->questions()->create([
            'question_text' => 'Question 1',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order_index' => 0,
        ]);

        $q2 = $assessment->questions()->create([
            'question_text' => 'Question 2',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order_index' => 1,
        ]);

        // Reorder: swap them
        $this->questionService->reorderQuestions($assessment, [$q2->id, $q1->id]);

        $this->assertEquals(0, $q2->fresh()->order_index);
        $this->assertEquals(1, $q1->fresh()->order_index);
    }

    public function test_can_get_randomized_questions()
    {
        $assessment = Assessment::factory()->create(['randomize_questions' => true]);
        
        for ($i = 0; $i < 5; $i++) {
            $assessment->questions()->create([
                'question_text' => "Question $i",
                'question_type' => 'multiple_choice',
                'points' => 1,
                'order_index' => $i,
            ]);
        }

        $questions = $this->questionService->getRandomizedQuestions($assessment);

        $this->assertEquals(5, $questions->count());
    }

    public function test_can_validate_correct_response()
    {
        $assessment = Assessment::factory()->create();
        $question = $assessment->questions()->create([
            'question_text' => 'Test Question',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order_index' => 0,
        ]);

        $correctOption = $question->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $question->options()->create([
            'option_text' => 'Wrong',
            'is_correct' => false,
            'order_index' => 1,
        ]);

        $isValid = $this->questionService->validateResponse($question, $correctOption->id);

        $this->assertTrue($isValid);
    }

    public function test_can_calculate_score_for_correct_response()
    {
        $assessment = Assessment::factory()->create();
        $question = $assessment->questions()->create([
            'question_text' => 'Test Question',
            'question_type' => 'multiple_choice',
            'points' => 5,
            'order_index' => 0,
        ]);

        $correctOption = $question->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $score = $this->questionService->calculateScore($question, $correctOption->id);

        $this->assertEquals(5, $score);
    }

    public function test_can_clone_question()
    {
        $assessment1 = Assessment::factory()->create();
        $assessment2 = Assessment::factory()->create();

        $question = $assessment1->questions()->create([
            'question_text' => 'Original Question',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order_index' => 0,
        ]);

        $question->options()->create([
            'option_text' => 'Option 1',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $cloned = $this->questionService->cloneQuestion($question, $assessment2);

        $this->assertNotEquals($question->id, $cloned->id);
        $this->assertEquals($question->question_text, $cloned->question_text);
        $this->assertEquals($assessment2->id, $cloned->assessment_id);
        $this->assertEquals($question->options()->count(), $cloned->options()->count());
    }
}
