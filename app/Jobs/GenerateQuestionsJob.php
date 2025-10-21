<?php

namespace App\Jobs;

use App\Models\AIQuestionJob;
use App\Models\User;
use App\Services\AI\AIQuestionGeneratorService;
use App\Notifications\QuestionGenerationCompleted;
use App\Notifications\QuestionGenerationFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public AIQuestionJob $job;
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(AIQuestionJob $job)
    {
        $this->job = $job;
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(AIQuestionGeneratorService $generatorService): void
    {
        try {
            // Mark job as processing
            $this->job->markAsProcessing();

            Log::info('Starting question generation', [
                'job_id' => $this->job->id,
                'lesson_id' => $this->job->lesson_id,
                'parameters' => $this->job->parameters
            ]);

            // Load lesson with relationships
            $lesson = $this->job->lesson()->with('module.course')->first();

            if (!$lesson) {
                throw new \Exception('Lesson not found');
            }

            // Generate questions
            $questions = $generatorService->generateQuestionsFromLesson(
                $lesson,
                $this->job->parameters
            );

            if (empty($questions)) {
                throw new \Exception('No questions were generated');
            }

            // Save generated questions
            $savedCount = $generatorService->saveGeneratedQuestions($this->job, $questions);

            // Mark job as completed
            $this->job->markAsCompleted($savedCount);

            Log::info('Question generation completed', [
                'job_id' => $this->job->id,
                'questions_generated' => $savedCount
            ]);

            // Notify the user who requested the generation
            $this->notifyUser(true, $savedCount);

        } catch (\Exception $e) {
            $this->handleFailure($e);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }

    /**
     * Handle failure and notify user
     */
    protected function handleFailure(\Throwable $exception): void
    {
        $errorMessage = $exception->getMessage();

        Log::error('Question generation failed', [
            'job_id' => $this->job->id,
            'lesson_id' => $this->job->lesson_id,
            'error' => $errorMessage,
            'trace' => $exception->getTraceAsString()
        ]);

        // Mark job as failed
        $this->job->markAsFailed($errorMessage);

        // Notify the user
        $this->notifyUser(false, 0, $errorMessage);
    }

    /**
     * Notify the user about job completion or failure
     */
    protected function notifyUser(bool $success, int $questionsCount = 0, string $errorMessage = null): void
    {
        try {
            $user = User::find($this->job->requested_by);

            if (!$user) {
                return;
            }

            if ($success) {
                $user->notify(new QuestionGenerationCompleted($this->job, $questionsCount));
            } else {
                $user->notify(new QuestionGenerationFailed($this->job, $errorMessage));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'job_id' => $this->job->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'ai-question-generation',
            'job:' . $this->job->id,
            'lesson:' . $this->job->lesson_id,
            'user:' . $this->job->requested_by,
        ];
    }
}
