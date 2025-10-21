<?php

namespace Tests\Feature\AI;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\Assessment;
use App\Models\AIQuestionJob;
use App\Models\GeneratedQuestion;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Jobs\GenerateQuestionsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class QuestionGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;
    protected Course $course;
    protected CourseLesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['role' => 'instructor']);
        $this->course = Course::factory()->create(['created_by' => $this->instructor->id]);
        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = CourseLesson::factory()->create([
            'module_id' => $module->id,
            'content_type' => 'text',
            'content_path' => 'Test content for question generation.',
        ]);
    }

    public function test_instructor_can_access_question_generator_form()
    {
        $response = $this->actingAs($this->instructor)
            ->get(route('admin.questions.generator.create', ['lesson_id' => $this->lesson->id]));

        $response->assertOk();
        $response->assertViewIs('admin.questions.generate');
        $response->assertViewHas('lesson', $this->lesson);
    }

    public function test_instructor_can_trigger_question_generation()
    {
        Queue::fake();

        $response = $this->actingAs($this->instructor)
            ->post(route('admin.questions.generator.generate'), [
                'lesson_id' => $this->lesson->id,
                'count' => 5,
                'types' => ['multiple_choice', 'true_false'],
                'difficulty' => 'medium',
                'include_explanation' => true,
                'points_per_question' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ai_question_jobs', [
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
            'status' => 'pending',
        ]);

        Queue::assertPushed(GenerateQuestionsJob::class);
    }

    public function test_question_generation_requires_valid_parameters()
    {
        $response = $this->actingAs($this->instructor)
            ->post(route('admin.questions.generator.generate'), [
                'lesson_id' => $this->lesson->id,
                'count' => 0, // Invalid
                'types' => [], // Invalid
                'difficulty' => 'invalid', // Invalid
                'points_per_question' => -1, // Invalid
            ]);

        $response->assertSessionHasErrors(['count', 'types', 'difficulty', 'points_per_question']);
    }

    public function test_instructor_can_view_generation_status()
    {
        $job = AIQuestionJob::factory()->create([
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
            'status' => 'processing',
        ]);

        $response = $this->actingAs($this->instructor)
            ->get(route('admin.questions.generator.status', $job));

        $response->assertOk();
        $response->assertViewIs('admin.questions.generation-status');
        $response->assertViewHas('job', $job);
    }

    public function test_instructor_can_review_generated_questions()
    {
        $job = AIQuestionJob::factory()->create([
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
            'status' => 'completed',
            'questions_count' => 3,
        ]);

        $questions = GeneratedQuestion::factory()->count(3)->create([
            'job_id' => $job->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->instructor)
            ->get(route('admin.questions.generator.review', $job));

        $response->assertOk();
        $response->assertViewIs('admin.questions.review');
        $response->assertViewHas('questions');
    }

    public function test_instructor_can_approve_generated_question()
    {
        $assessment = Assessment::factory()->create([
            'course_id' => $this->course->id,
        ]);

        $job = AIQuestionJob::factory()->create([
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
        ]);

        $generatedQuestion = GeneratedQuestion::factory()->create([
            'job_id' => $job->id,
            'status' => 'pending',
            'question_type' => 'multiple_choice',
            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
            'correct_answer' => [1],
        ]);

        $response = $this->actingAs($this->instructor)
            ->postJson(route('admin.questions.generator.approve', $generatedQuestion), [
                'assessment_id' => $assessment->id,
                'review_notes' => 'Looks good',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('generated_questions', [
            'id' => $generatedQuestion->id,
            'status' => 'approved',
            'reviewed_by' => $this->instructor->id,
        ]);

        $this->assertDatabaseHas('questions', [
            'assessment_id' => $assessment->id,
            'question_text' => $generatedQuestion->question_text,
        ]);
    }

    public function test_instructor_can_reject_generated_question()
    {
        $job = AIQuestionJob::factory()->create([
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
        ]);

        $generatedQuestion = GeneratedQuestion::factory()->create([
            'job_id' => $job->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->instructor)
            ->postJson(route('admin.questions.generator.reject', $generatedQuestion), [
                'review_notes' => 'Not accurate',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('generated_questions', [
            'id' => $generatedQuestion->id,
            'status' => 'rejected',
            'reviewed_by' => $this->instructor->id,
            'review_notes' => 'Not accurate',
        ]);
    }

    public function test_instructor_can_edit_generated_question()
    {
        $job = AIQuestionJob::factory()->create([
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
        ]);

        $generatedQuestion = GeneratedQuestion::factory()->create([
            'job_id' => $job->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->instructor)
            ->get(route('admin.questions.generator.edit', $generatedQuestion));

        $response->assertOk();
        $response->assertViewIs('admin.questions.edit-generated');
        $response->assertViewHas('generatedQuestion', $generatedQuestion);
    }

    public function test_instructor_can_update_generated_question()
    {
        $job = AIQuestionJob::factory()->create([
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
        ]);

        $generatedQuestion = GeneratedQuestion::factory()->create([
            'job_id' => $job->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->instructor)
            ->put(route('admin.questions.generator.update', $generatedQuestion), [
                'question_text' => 'Updated question text?',
                'question_type' => 'multiple_choice',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => [0],
                'explanation' => 'Updated explanation',
                'points' => 2,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('generated_questions', [
            'id' => $generatedQuestion->id,
            'question_text' => 'Updated question text?',
            'points' => 2,
        ]);
    }

    public function test_instructor_can_bulk_approve_questions()
    {
        $assessment = Assessment::factory()->create([
            'course_id' => $this->course->id,
        ]);

        $job = AIQuestionJob::factory()->create([
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
        ]);

        $questions = GeneratedQuestion::factory()->count(3)->create([
            'job_id' => $job->id,
            'status' => 'pending',
            'question_type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => [0],
        ]);

        $response = $this->actingAs($this->instructor)
            ->postJson(route('admin.questions.generator.bulk-approve'), [
                'question_ids' => $questions->pluck('id')->toArray(),
                'assessment_id' => $assessment->id,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'approved' => 3]);

        foreach ($questions as $question) {
            $this->assertDatabaseHas('generated_questions', [
                'id' => $question->id,
                'status' => 'approved',
            ]);
        }

        $this->assertDatabaseCount('questions', 3);
    }

    public function test_unauthorized_user_cannot_access_question_generator()
    {
        $student = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($student)
            ->get(route('admin.questions.generator.create', ['lesson_id' => $this->lesson->id]));

        $response->assertForbidden();
    }

    public function test_question_generation_job_processes_successfully()
    {
        // Mock AI service
        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateText')
            ->once()
            ->andReturn(json_encode([
                [
                    'question_text' => 'Test question?',
                    'question_type' => 'multiple_choice',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 0,
                    'explanation' => 'Test explanation',
                ]
            ]));

        $this->app->instance(AIServiceInterface::class, $mockAIService);

        $job = AIQuestionJob::factory()->create([
            'lesson_id' => $this->lesson->id,
            'requested_by' => $this->instructor->id,
            'status' => 'pending',
            'parameters' => [
                'count' => 1,
                'types' => ['multiple_choice'],
                'difficulty' => 'medium',
                'include_explanation' => true,
                'points_per_question' => 1,
            ],
        ]);

        $jobInstance = new GenerateQuestionsJob($job);
        $jobInstance->handle($this->app->make(\App\Services\AI\AIQuestionGeneratorService::class));

        $this->assertDatabaseHas('ai_question_jobs', [
            'id' => $job->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('generated_questions', [
            'job_id' => $job->id,
            'question_text' => 'Test question?',
            'status' => 'pending',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
