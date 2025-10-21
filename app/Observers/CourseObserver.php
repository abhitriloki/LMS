<?php

namespace App\Observers;

use App\Models\Course;
use App\Services\CacheInvalidationService;

class CourseObserver
{
    protected CacheInvalidationService $cacheService;

    public function __construct(CacheInvalidationService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Course "created" event.
     */
    public function created(Course $course): void
    {
        $this->cacheService->invalidateCatalog();
    }

    /**
     * Handle the Course "updated" event.
     */
    public function updated(Course $course): void
    {
        $this->cacheService->invalidateCourse($course);
        
        // If published status changed, invalidate catalog
        if ($course->wasChanged('is_published')) {
            $this->cacheService->invalidateCatalog();
        }
    }

    /**
     * Handle the Course "deleted" event.
     */
    public function deleted(Course $course): void
    {
        $this->cacheService->invalidateCourse($course);
        $this->cacheService->invalidateCatalog();
    }
}
