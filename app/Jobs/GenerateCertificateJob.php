<?php

namespace App\Jobs;

use App\Models\Enrollment;
use App\Services\CertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Enrollment $enrollment;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(Enrollment $enrollment)
    {
        $this->enrollment = $enrollment;
        
        // Use high priority queue for certificate generation
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(CertificateService $certificateService): void
    {
        try {
            // Generate certificate
            $certificate = $certificateService->generate($this->enrollment);

            // Send email notification
            $certificateService->sendEmail($certificate);

            Log::info('Certificate generated successfully', [
                'certificate_id' => $certificate->id,
                'enrollment_id' => $this->enrollment->id,
                'user_id' => $this->enrollment->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate certificate', [
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
        Log::error('Certificate generation job failed permanently', [
            'enrollment_id' => $this->enrollment->id,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify admin about the failure
        // Admin::notify(new CertificateGenerationFailed($this->enrollment, $exception));
    }
}
