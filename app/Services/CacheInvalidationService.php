<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheInvalidationService
{
    /**
     * Invalidate course-related caches
     */
    public function invalidateCourse(Course $course): void
    {
        $tags = [
            "course.{$course->id}.related",
            "catalog.popular_tags",
        ];

        foreach ($tags as $tag) {
            Cache::forget($tag);
        }

        Log::info('Course cache invalidated', ['course_id' => $course->id]);
    }

    /**
     * Invalidate catalog caches
     */
    public function invalidateCatalog(): void
    {
        Cache::forget('catalog.categories');
        Cache::forget('catalog.popular_tags');

        Log::info('Catalog cache invalidated');
    }

    /**
     * Invalidate enrollment progress caches
     */
    public function invalidateEnrollmentProgress(Enrollment $enrollment): void
    {
        Cache::forget("enrollment.{$enrollment->id}.progress");
        Cache::forget("user.{$enrollment->user_id}.progress");

        Log::info('Enrollment progress cache invalidated', [
            'enrollment_id' => $enrollment->id,
            'user_id' => $enrollment->user_id,
        ]);
    }

    /**
     * Invalidate user progress caches
     */
    public function invalidateUserProgress(User $user): void
    {
        Cache::forget("user.{$user->id}.progress");

        // Invalidate all enrollment progress for this user
        $enrollments = Enrollment::where('user_id', $user->id)->get();
        foreach ($enrollments as $enrollment) {
            Cache::forget("enrollment.{$enrollment->id}.progress");
        }

        Log::info('User progress cache invalidated', ['user_id' => $user->id]);
    }

    /**
     * Invalidate AI response caches for a specific pattern
     */
    public function invalidateAICache(string $pattern = null): void
    {
        if ($pattern) {
            // This would require Redis SCAN or similar for pattern matching
            // For now, we'll log the request
            Log::info('AI cache invalidation requested', ['pattern' => $pattern]);
        } else {
            // Clear all AI caches (use with caution)
            Log::warning('Full AI cache invalidation requested');
        }
    }

    /**
     * Invalidate all caches (use with caution)
     */
    public function invalidateAll(): void
    {
        Cache::flush();
        Log::warning('All caches flushed');
    }

    /**
     * Warm up catalog cache
     */
    public function warmCatalogCache(): void
    {
        // Pre-load categories
        Cache::remember('catalog.categories', 3600, function () {
            return \App\Models\CourseCategory::with('children')
                ->whereNull('parent_id')
                ->orderBy('order_index')
                ->get();
        });

        // Pre-load popular tags
        Cache::remember('catalog.popular_tags', 1800, function () {
            $courses = Course::published()
                ->whereNotNull('tags')
                ->get();

            $tagCounts = [];
            foreach ($courses as $course) {
                if (is_array($course->tags)) {
                    foreach ($course->tags as $tag) {
                        $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                    }
                }
            }

            arsort($tagCounts);
            return array_slice(array_keys($tagCounts), 0, 20);
        });

        Log::info('Catalog cache warmed up');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This is a simplified version - actual implementation would depend on cache driver
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'status' => 'operational',
        ];
    }
}
