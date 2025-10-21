<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProgressTrackingService
{
    /**
     * Calculate and update enrollment progress
     */
    public function updateEnrollmentProgress(Enrollment $enrollment): array
    {
        $course = $enrollment->course;
        $totalLessons = $this->getTotalLessonsCount($course);

        if ($totalLessons === 0) {
            return [
                'progress_percentage' => 0,
                'completed_lessons' => 0,
                'total_lessons' => 0,
            ];
        }

        $completedLessons = $this->getCompletedLessonsCount($enrollment);
        $progressPercentage = ($completedLessons / $totalLessons) * 100;

        // Update enrollment
        $enrollment->update([
            'progress_percentage' => round($progressPercentage, 2),
            'last_accessed_at' => now(),
        ]);

        // Invalidate progress cache
        Cache::forget("enrollment.{$enrollment->id}.progress");
        Cache::forget("user.{$enrollment->user_id}.progress");

        // Check if course should be marked as completed
        if ($progressPercentage >= 100 && !$enrollment->isCompleted()) {
            $this->markEnrollmentComplete($enrollment);
        }

        return [
            'progress_percentage' => round($progressPercentage, 2),
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
        ];
    }

    /**
     * Calculate course completion percentage for a user
     */
    public function calculateCourseCompletion(Enrollment $enrollment): float
    {
        $course = $enrollment->course;
        $totalLessons = $this->getTotalLessonsCount($course);

        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = $this->getCompletedLessonsCount($enrollment);

        return round(($completedLessons / $totalLessons) * 100, 2);
    }

    /**
     * Get total lessons count for a course
     */
    public function getTotalLessonsCount(Course $course): int
    {
        return DB::table('course_lessons')
            ->join('course_modules', 'course_lessons.module_id', '=', 'course_modules.id')
            ->where('course_modules.course_id', $course->id)
            ->count();
    }

    /**
     * Get completed lessons count for an enrollment
     */
    public function getCompletedLessonsCount(Enrollment $enrollment): int
    {
        return LessonProgress::where('enrollment_id', $enrollment->id)
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get in-progress lessons count for an enrollment
     */
    public function getInProgressLessonsCount(Enrollment $enrollment): int
    {
        return LessonProgress::where('enrollment_id', $enrollment->id)
            ->where('status', 'in_progress')
            ->count();
    }

    /**
     * Get progress statistics for an enrollment
     */
    public function getProgressStats(Enrollment $enrollment): array
    {
        // Cache progress stats for 5 minutes
        return Cache::remember(
            "enrollment.{$enrollment->id}.progress",
            300,
            function () use ($enrollment) {
                $course = $enrollment->course;
                $totalLessons = $this->getTotalLessonsCount($course);
                $completedLessons = $this->getCompletedLessonsCount($enrollment);
                $inProgressLessons = $this->getInProgressLessonsCount($enrollment);
                $notStartedLessons = $totalLessons - $completedLessons - $inProgressLessons;

                $totalTimeSpent = LessonProgress::where('enrollment_id', $enrollment->id)
                    ->sum('time_spent');

                return [
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'in_progress_lessons' => $inProgressLessons,
                    'not_started_lessons' => $notStartedLessons,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'total_time_spent' => $totalTimeSpent,
                    'estimated_time_remaining' => $this->estimateTimeRemaining($enrollment),
                ];
            }
        );
    }

    /**
     * Estimate time remaining for course completion
     */
    protected function estimateTimeRemaining(Enrollment $enrollment): int
    {
        $course = $enrollment->course;
        $completedLessons = $this->getCompletedLessonsCount($enrollment);
        $totalLessons = $this->getTotalLessonsCount($course);

        if ($completedLessons === 0 || $totalLessons === 0) {
            return $course->estimated_duration ?? 0;
        }

        $totalTimeSpent = LessonProgress::where('enrollment_id', $enrollment->id)
            ->sum('time_spent');

        $averageTimePerLesson = $totalTimeSpent / $completedLessons;
        $remainingLessons = $totalLessons - $completedLessons;

        return (int) ($averageTimePerLesson * $remainingLessons);
    }

    /**
     * Mark enrollment as complete
     */
    protected function markEnrollmentComplete(Enrollment $enrollment): void
    {
        $enrollment->markAsCompleted();

        Log::info('Course completed', [
            'user_id' => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'enrollment_id' => $enrollment->id,
            'completion_date' => $enrollment->completion_date,
        ]);

        // Dispatch event for certificate generation, notifications, etc.
        event(new \App\Events\CourseCompleted($enrollment));
    }

    /**
     * Get progress for all enrollments of a user
     */
    public function getUserProgress(User $user): array
    {
        // Cache user progress for 10 minutes
        return Cache::remember(
            "user.{$user->id}.progress",
            600,
            function () use ($user) {
                $enrollments = Enrollment::where('user_id', $user->id)
                    ->with('course')
                    ->get();

                $stats = [
                    'total_courses' => $enrollments->count(),
                    'completed_courses' => $enrollments->where('status', 'completed')->count(),
                    'in_progress_courses' => $enrollments->where('status', 'active')->count(),
                    'total_time_spent' => 0,
                    'average_progress' => 0,
                ];

                foreach ($enrollments as $enrollment) {
                    $stats['total_time_spent'] += LessonProgress::where('enrollment_id', $enrollment->id)
                        ->sum('time_spent');
                }

                if ($stats['total_courses'] > 0) {
                    $stats['average_progress'] = round(
                        $enrollments->avg('progress_percentage'),
                        2
                    );
                }

                return $stats;
            }
        );
    }

    /**
     * Get detailed progress for a specific enrollment
     */
    public function getDetailedProgress(Enrollment $enrollment): array
    {
        $course = $enrollment->course->load(['modules.lessons']);
        $progressData = [];

        foreach ($course->modules as $module) {
            $moduleData = [
                'module_id' => $module->id,
                'module_title' => $module->title,
                'lessons' => [],
                'completed_count' => 0,
                'total_count' => $module->lessons->count(),
            ];

            foreach ($module->lessons as $lesson) {
                $progress = LessonProgress::where('enrollment_id', $enrollment->id)
                    ->where('lesson_id', $lesson->id)
                    ->first();

                $lessonData = [
                    'lesson_id' => $lesson->id,
                    'lesson_title' => $lesson->title,
                    'content_type' => $lesson->content_type,
                    'duration' => $lesson->duration,
                    'status' => $progress->status ?? 'not_started',
                    'progress_percentage' => $progress->progress_percentage ?? 0,
                    'time_spent' => $progress->time_spent ?? 0,
                    'last_accessed_at' => $progress->last_accessed_at ?? null,
                    'completed_at' => $progress->completed_at ?? null,
                ];

                if ($progress && $progress->status === 'completed') {
                    $moduleData['completed_count']++;
                }

                $moduleData['lessons'][] = $lessonData;
            }

            $moduleData['progress_percentage'] = $moduleData['total_count'] > 0
                ? round(($moduleData['completed_count'] / $moduleData['total_count']) * 100, 2)
                : 0;

            $progressData[] = $moduleData;
        }

        return [
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'progress_percentage' => $enrollment->progress_percentage,
                'enrollment_date' => $enrollment->enrollment_date,
                'completion_date' => $enrollment->completion_date,
                'deadline' => $enrollment->deadline,
            ],
            'modules' => $progressData,
            'stats' => $this->getProgressStats($enrollment),
        ];
    }

    /**
     * Recalculate progress for all enrollments (maintenance task)
     */
    public function recalculateAllProgress(): array
    {
        $enrollments = Enrollment::where('status', '!=', 'completed')->get();
        $updated = 0;
        $errors = 0;

        foreach ($enrollments as $enrollment) {
            try {
                $this->updateEnrollmentProgress($enrollment);
                $updated++;
            } catch (\Exception $e) {
                Log::error('Failed to recalculate progress', [
                    'enrollment_id' => $enrollment->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        return [
            'total' => $enrollments->count(),
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
}
