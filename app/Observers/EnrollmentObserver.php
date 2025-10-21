<?php

namespace App\Observers;

use App\Models\Enrollment;
use App\Services\CacheInvalidationService;

class EnrollmentObserver
{
    protected CacheInvalidationService $cacheService;

    public function __construct(CacheInvalidationService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Enrollment "created" event.
     */
    public function created(Enrollment $enrollment): void
    {
        $this->cacheService->invalidateUserProgress($enrollment->user);
    }

    /**
     * Handle the Enrollment "updated" event.
     */
    public function updated(Enrollment $enrollment): void
    {
        $this->cacheService->invalidateEnrollmentProgress($enrollment);
    }

    /**
     * Handle the Enrollment "deleted" event.
     */
    public function deleted(Enrollment $enrollment): void
    {
        $this->cacheService->invalidateUserProgress($enrollment->user);
    }
}
