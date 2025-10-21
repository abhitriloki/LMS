<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'event_category',
        'event_data',
        'ip_address',
        'user_agent',
        'session_id',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    /**
     * Get the user who triggered this event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Query Scopes

    /**
     * Scope to filter by event type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope to filter by event category
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('event_category', $category);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent events
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Static Methods

    /**
     * Track an event
     */
    public static function track(
        string $eventType,
        string $eventCategory,
        ?User $user = null,
        array $eventData = []
    ): self {
        return static::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'event_category' => $eventCategory,
            'event_data' => $eventData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Track course view
     */
    public static function trackCourseView(Course $course, User $user): self
    {
        return static::track('course_view', 'course', $user, [
            'course_id' => $course->id,
            'course_title' => $course->title,
        ]);
    }

    /**
     * Track lesson view
     */
    public static function trackLessonView(CourseLesson $lesson, User $user): self
    {
        return static::track('lesson_view', 'content', $user, [
            'lesson_id' => $lesson->id,
            'lesson_title' => $lesson->title,
            'module_id' => $lesson->module_id,
        ]);
    }

    /**
     * Track enrollment
     */
    public static function trackEnrollment(Enrollment $enrollment): self
    {
        return static::track('enrollment', 'course', $enrollment->user, [
            'course_id' => $enrollment->course_id,
            'enrollment_type' => $enrollment->enrollment_type,
        ]);
    }

    /**
     * Track course completion
     */
    public static function trackCourseCompletion(Enrollment $enrollment): self
    {
        return static::track('course_completion', 'course', $enrollment->user, [
            'course_id' => $enrollment->course_id,
            'final_score' => $enrollment->final_score,
            'completion_date' => $enrollment->completion_date,
        ]);
    }

    /**
     * Track assessment attempt
     */
    public static function trackAssessmentAttempt(AssessmentAttempt $attempt): self
    {
        return static::track('assessment_attempt', 'assessment', $attempt->user, [
            'assessment_id' => $attempt->assessment_id,
            'score' => $attempt->score,
            'passed' => $attempt->passed,
        ]);
    }
}
