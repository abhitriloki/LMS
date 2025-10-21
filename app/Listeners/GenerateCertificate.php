<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Jobs\GenerateCertificateJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GenerateCertificate implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment;

        // Check if certificate already exists
        if ($enrollment->certificate_id) {
            return;
        }

        // Check if course requires certificate
        $course = $enrollment->course;
        $metadata = $course->metadata ?? [];
        
        // Skip if course doesn't issue certificates
        if (isset($metadata['issue_certificate']) && $metadata['issue_certificate'] === false) {
            return;
        }

        // Dispatch job to generate certificate
        GenerateCertificateJob::dispatch($enrollment);
    }
}
