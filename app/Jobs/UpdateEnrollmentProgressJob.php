<?php

namespace App\Jobs;

use App\Models\Enrollment;
use App\Services\ProgressTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateEnrollmentProgressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Enrollment $enrollment;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Enrollment $enrollment)
    {
        $this->enrollment = $enrollment;
    }

    /**
     * Execute the job.
     */
    public function handle(ProgressTrackingService $progressService): void
    {
        try {
            $result = $progressService->updateEnrollmentProgress($this->enrollment);

            Log::info('Enrollment progress updated', [
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->enrollment->user_id,
                'course_id' => $this->enrollment->course_id,
                'progress_percentage' => $result['progress_percentage'],
                'completed_lessons' => $result['completed_lessons'],
                'total_lessons' => $result['total_lessons'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update enrollment progress', [
                'enrollment_id' => $this->enrollment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateEnrollmentProgressJob failed permanently', [
            'enrollment_id' => $this->enrollment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
