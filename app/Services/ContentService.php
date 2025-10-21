<?php

namespace App\Services;

use App\Jobs\UpdateEnrollmentProgressJob;
use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ContentService
{
    /**
     * Get lesson content for a user
     */
    public function getLesson(int $lessonId, User $user): array
    {
        $lesson = CourseLesson::with(['module.course'])->findOrFail($lessonId);
        $course = $lesson->module->course;

        // Check if user is enrolled
        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Get or create lesson progress
        $progress = $this->getOrCreateProgress($enrollment, $lesson);

        // Update last accessed time
        $progress->update(['last_accessed_at' => now()]);
        $enrollment->update(['last_accessed_at' => now()]);

        return [
            'lesson' => $lesson,
            'course' => $course,
            'enrollment' => $enrollment,
            'progress' => $progress,
            'content_url' => $this->getContentUrl($lesson),
            'is_downloadable' => $lesson->is_downloadable,
        ];
    }

    /**
     * Track progress for a lesson
     */
    public function trackProgress(User $user, CourseLesson $lesson, array $data): LessonProgress
    {
        $enrollment = Enrollment::where('course_id', $lesson->module->course_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $progress = $this->getOrCreateProgress($enrollment, $lesson);

        // Update progress
        $percentage = $data['progress_percentage'] ?? $progress->progress_percentage;
        $position = $data['position'] ?? null;
        $timeSpent = $data['time_spent'] ?? $progress->time_spent;

        $progress->updateProgress($percentage, $position);

        // Update time spent if provided
        if (isset($data['time_spent'])) {
            $progress->update(['time_spent' => $timeSpent]);
        }

        // Update enrollment progress asynchronously
        UpdateEnrollmentProgressJob::dispatch($enrollment);

        return $progress->fresh();
    }

    /**
     * Bookmark current position in content
     */
    public function bookmarkPosition(User $user, CourseLesson $lesson, int $position): LessonProgress
    {
        $enrollment = Enrollment::where('course_id', $lesson->module->course_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $progress = $this->getOrCreateProgress($enrollment, $lesson);

        $progress->update([
            'last_position' => $position,
            'last_accessed_at' => now(),
        ]);

        return $progress->fresh();
    }

    /**
     * Mark lesson as complete
     */
    public function markComplete(User $user, CourseLesson $lesson): LessonProgress
    {
        $enrollment = Enrollment::where('course_id', $lesson->module->course_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $progress = $this->getOrCreateProgress($enrollment, $lesson);

        $progress->markAsCompleted();

        // Update enrollment progress asynchronously
        UpdateEnrollmentProgressJob::dispatch($enrollment);

        // Log completion event
        Log::info('Lesson completed', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'course_id' => $lesson->module->course_id,
        ]);

        return $progress->fresh();
    }

    /**
     * Get download URL for lesson content
     */
    public function getDownloadUrl(CourseLesson $lesson, User $user): ?string
    {
        if (!$lesson->is_downloadable) {
            return null;
        }

        // Check if user is enrolled
        $enrollment = Enrollment::where('course_id', $lesson->module->course_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$enrollment) {
            return null;
        }

        if ($lesson->content_path) {
            return Storage::temporaryUrl($lesson->content_path, now()->addHours(1));
        }

        return $lesson->content_url;
    }

    /**
     * Get or create lesson progress record
     */
    protected function getOrCreateProgress(Enrollment $enrollment, CourseLesson $lesson): LessonProgress
    {
        return LessonProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'status' => 'not_started',
                'progress_percentage' => 0,
                'time_spent' => 0,
                'first_accessed_at' => now(),
                'last_accessed_at' => now(),
            ]
        );
    }

    /**
     * Get content URL for lesson
     */
    protected function getContentUrl(CourseLesson $lesson): ?string
    {
        if ($lesson->content_url) {
            return $lesson->content_url;
        }

        if ($lesson->content_path) {
            // Generate temporary signed URL for secure access
            return Storage::temporaryUrl($lesson->content_path, now()->addHours(2));
        }

        return null;
    }



    /**
     * Get next lesson in the course
     */
    public function getNextLesson(CourseLesson $currentLesson): ?CourseLesson
    {
        $module = $currentLesson->module;
        $course = $module->course;

        // Try to get next lesson in same module
        $nextLesson = CourseLesson::where('module_id', $module->id)
            ->where('order_index', '>', $currentLesson->order_index)
            ->orderBy('order_index')
            ->first();

        if ($nextLesson) {
            return $nextLesson;
        }

        // Get next module
        $nextModule = CourseModule::where('course_id', $course->id)
            ->where('order_index', '>', $module->order_index)
            ->orderBy('order_index')
            ->first();

        if ($nextModule) {
            return $nextModule->lessons()->orderBy('order_index')->first();
        }

        return null;
    }

    /**
     * Get previous lesson in the course
     */
    public function getPreviousLesson(CourseLesson $currentLesson): ?CourseLesson
    {
        $module = $currentLesson->module;
        $course = $module->course;

        // Try to get previous lesson in same module
        $previousLesson = CourseLesson::where('module_id', $module->id)
            ->where('order_index', '<', $currentLesson->order_index)
            ->orderBy('order_index', 'desc')
            ->first();

        if ($previousLesson) {
            return $previousLesson;
        }

        // Get previous module
        $previousModule = CourseModule::where('course_id', $course->id)
            ->where('order_index', '<', $module->order_index)
            ->orderBy('order_index', 'desc')
            ->first();

        if ($previousModule) {
            return $previousModule->lessons()->orderBy('order_index', 'desc')->first();
        }

        return null;
    }
}
