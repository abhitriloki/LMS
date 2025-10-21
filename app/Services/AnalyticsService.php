<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\AssessmentAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Track an analytics event
     */
    public function trackEvent(string $eventType, User $user, array $properties = []): AnalyticsEvent
    {
        $category = $this->determineCategory($eventType);
        
        return AnalyticsEvent::track($eventType, $category, $user, $properties);
    }

    /**
     * Get user analytics
     */
    public function getUserAnalytics(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "user_analytics_{$user->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 3600, function () use ($user, $startDate, $endDate) {
            return [
                'enrollments' => $this->getUserEnrollmentStats($user, $startDate, $endDate),
                'progress' => $this->getUserProgressStats($user),
                'assessments' => $this->getUserAssessmentStats($user, $startDate, $endDate),
                'activity' => $this->getUserActivityStats($user, $startDate, $endDate),
                'certificates' => $this->getUserCertificateStats($user),
                'learning_time' => $this->getUserLearningTime($user, $startDate, $endDate),
            ];
        });
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics(Course $course, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "course_analytics_{$course->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 1800, function () use ($course, $startDate, $endDate) {
            return [
                'enrollments' => $this->getCourseEnrollmentStats($course, $startDate, $endDate),
                'completion' => $this->getCourseCompletionStats($course),
                'engagement' => $this->getCourseEngagementStats($course, $startDate, $endDate),
                'assessments' => $this->getCourseAssessmentStats($course, $startDate, $endDate),
                'ratings' => $this->getCourseRatingStats($course),
                'popular_lessons' => $this->getPopularLessons($course, $startDate, $endDate),
            ];
        });
    }

    /**
     * Get department analytics
     */
    public function getDepartmentAnalytics(Department $department, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "department_analytics_{$department->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 1800, function () use ($department, $startDate, $endDate) {
            return [
                'users' => $this->getDepartmentUserStats($department),
                'enrollments' => $this->getDepartmentEnrollmentStats($department, $startDate, $endDate),
                'completion' => $this->getDepartmentCompletionStats($department),
                'compliance' => $this->getDepartmentComplianceStats($department),
                'top_performers' => $this->getDepartmentTopPerformers($department, $startDate, $endDate),
                'popular_courses' => $this->getDepartmentPopularCourses($department, $startDate, $endDate),
            ];
        });
    }

    /**
     * Get dashboard metrics
     */
    public function getDashboardMetrics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return [
            'total_users' => User::count(),
            'active_users' => $this->getActiveUsersCount($startDate, $endDate),
            'total_courses' => Course::where('is_published', true)->count(),
            'total_enrollments' => Enrollment::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed_courses' => Enrollment::where('status', 'completed')
                ->whereBetween('completion_date', [$startDate, $endDate])
                ->count(),
            'certificates_issued' => $this->getCertificatesIssuedCount($startDate, $endDate),
            'average_completion_rate' => $this->getAverageCompletionRate(),
            'total_learning_hours' => $this->getTotalLearningHours($startDate, $endDate),
        ];
    }

    /**
     * Get user enrollment statistics
     */
    protected function getUserEnrollmentStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $enrollments = $user->enrollments()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total' => $enrollments->count(),
            'active' => $enrollments->where('status', 'active')->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'in_progress' => $enrollments->where('status', 'active')
                ->where('progress_percentage', '>', 0)->count(),
        ];
    }

    /**
     * Get user progress statistics
     */
    protected function getUserProgressStats(User $user): array
    {
        $enrollments = $user->enrollments()->where('status', 'active')->get();

        return [
            'average_progress' => $enrollments->avg('progress_percentage') ?? 0,
            'courses_in_progress' => $enrollments->where('progress_percentage', '>', 0)->count(),
            'courses_not_started' => $enrollments->where('progress_percentage', 0)->count(),
        ];
    }

    /**
     * Get user assessment statistics
     */
    protected function getUserAssessmentStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $attempts = AssessmentAttempt::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total_attempts' => $attempts->count(),
            'passed' => $attempts->where('passed', true)->count(),
            'failed' => $attempts->where('passed', false)->count(),
            'average_score' => $attempts->avg('score') ?? 0,
        ];
    }

    /**
     * Get user activity statistics
     */
    protected function getUserActivityStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $events = AnalyticsEvent::forUser($user->id)
            ->dateRange($startDate, $endDate)
            ->get();

        return [
            'total_events' => $events->count(),
            'course_views' => $events->where('event_type', 'course_view')->count(),
            'lesson_views' => $events->where('event_type', 'lesson_view')->count(),
            'last_activity' => $user->last_login_at?->diffForHumans(),
        ];
    }

    /**
     * Get user certificate statistics
     */
    protected function getUserCertificateStats(User $user): array
    {
        $certificates = $user->certificates;

        return [
            'total' => $certificates->count(),
            'recent' => $certificates->where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Get user learning time
     */
    protected function getUserLearningTime(User $user, Carbon $startDate, Carbon $endDate): float
    {
        return DB::table('lesson_progress')
            ->where('user_id', $user->id)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('time_spent') / 60; // Convert to hours
    }

    /**
     * Get course enrollment statistics
     */
    protected function getCourseEnrollmentStats(Course $course, Carbon $startDate, Carbon $endDate): array
    {
        $enrollments = $course->enrollments()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total' => $course->enrollments()->count(),
            'new' => $enrollments->count(),
            'active' => $course->enrollments()->where('status', 'active')->count(),
            'completed' => $course->enrollments()->where('status', 'completed')->count(),
            'trend' => $this->getEnrollmentTrend($course, $startDate, $endDate),
        ];
    }

    /**
     * Get course completion statistics
     */
    protected function getCourseCompletionStats(Course $course): array
    {
        $totalEnrollments = $course->enrollments()->count();
        $completedEnrollments = $course->enrollments()->where('status', 'completed')->count();

        return [
            'total_enrollments' => $totalEnrollments,
            'completed' => $completedEnrollments,
            'completion_rate' => $totalEnrollments > 0 ? ($completedEnrollments / $totalEnrollments) * 100 : 0,
            'average_completion_time' => $this->getAverageCompletionTime($course),
        ];
    }

    /**
     * Get course engagement statistics
     */
    protected function getCourseEngagementStats(Course $course, Carbon $startDate, Carbon $endDate): array
    {
        $views = AnalyticsEvent::ofType('course_view')
            ->dateRange($startDate, $endDate)
            ->whereJsonContains('event_data->course_id', $course->id)
            ->count();

        $uniqueViewers = AnalyticsEvent::ofType('course_view')
            ->dateRange($startDate, $endDate)
            ->whereJsonContains('event_data->course_id', $course->id)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'total_views' => $views,
            'unique_viewers' => $uniqueViewers,
            'average_progress' => $course->enrollments()->avg('progress_percentage') ?? 0,
        ];
    }

    /**
     * Get course assessment statistics
     */
    protected function getCourseAssessmentStats(Course $course, Carbon $startDate, Carbon $endDate): array
    {
        $assessmentIds = $course->assessments()->pluck('id');
        
        $attempts = AssessmentAttempt::whereIn('assessment_id', $assessmentIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total_attempts' => $attempts->count(),
            'average_score' => $attempts->avg('score') ?? 0,
            'pass_rate' => $attempts->count() > 0 
                ? ($attempts->where('passed', true)->count() / $attempts->count()) * 100 
                : 0,
        ];
    }

    /**
     * Get course rating statistics
     */
    protected function getCourseRatingStats(Course $course): array
    {
        // Placeholder for future rating system
        return [
            'average_rating' => 0,
            'total_ratings' => 0,
        ];
    }

    /**
     * Get popular lessons
     */
    protected function getPopularLessons(Course $course, Carbon $startDate, Carbon $endDate): array
    {
        $lessonViews = AnalyticsEvent::ofType('lesson_view')
            ->dateRange($startDate, $endDate)
            ->get()
            ->groupBy('event_data.lesson_id')
            ->map(function ($events) {
                return $events->count();
            })
            ->sortDesc()
            ->take(5);

        return $lessonViews->toArray();
    }

    /**
     * Get department user statistics
     */
    protected function getDepartmentUserStats(Department $department): array
    {
        $users = $department->users;

        return [
            'total' => $users->count(),
            'active' => $users->where('last_login_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Get department enrollment statistics
     */
    protected function getDepartmentEnrollmentStats(Department $department, Carbon $startDate, Carbon $endDate): array
    {
        $userIds = $department->users->pluck('id');
        
        $enrollments = Enrollment::whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total' => $enrollments->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'in_progress' => $enrollments->where('status', 'active')->count(),
        ];
    }

    /**
     * Get department completion statistics
     */
    protected function getDepartmentCompletionStats(Department $department): array
    {
        $userIds = $department->users->pluck('id');
        
        $totalEnrollments = Enrollment::whereIn('user_id', $userIds)->count();
        $completedEnrollments = Enrollment::whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->count();

        return [
            'total_enrollments' => $totalEnrollments,
            'completed' => $completedEnrollments,
            'completion_rate' => $totalEnrollments > 0 
                ? ($completedEnrollments / $totalEnrollments) * 100 
                : 0,
        ];
    }

    /**
     * Get department compliance statistics
     */
    protected function getDepartmentComplianceStats(Department $department): array
    {
        $userIds = $department->users->pluck('id');
        $mandatoryCourses = Course::where('is_mandatory', true)->get();

        $totalRequired = $mandatoryCourses->count() * $userIds->count();
        $completed = 0;

        foreach ($mandatoryCourses as $course) {
            $completed += Enrollment::where('course_id', $course->id)
                ->whereIn('user_id', $userIds)
                ->where('status', 'completed')
                ->count();
        }

        return [
            'total_required' => $totalRequired,
            'completed' => $completed,
            'compliance_rate' => $totalRequired > 0 ? ($completed / $totalRequired) * 100 : 0,
            'overdue' => $this->getOverdueCount($department),
        ];
    }

    /**
     * Get department top performers
     */
    protected function getDepartmentTopPerformers(Department $department, Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        $userIds = $department->users->pluck('id');

        return User::whereIn('id', $userIds)
            ->withCount(['enrollments as completed_courses' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('completion_date', [$startDate, $endDate]);
            }])
            ->orderByDesc('completed_courses')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'completed_courses' => $user->completed_courses,
                ];
            })
            ->toArray();
    }

    /**
     * Get department popular courses
     */
    protected function getDepartmentPopularCourses(Department $department, Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        $userIds = $department->users->pluck('id');

        return Course::withCount(['enrollments as enrollment_count' => function ($query) use ($userIds, $startDate, $endDate) {
                $query->whereIn('user_id', $userIds)
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->orderByDesc('enrollment_count')
            ->limit($limit)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'enrollments' => $course->enrollment_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get active users count
     */
    protected function getActiveUsersCount(Carbon $startDate, Carbon $endDate): int
    {
        return User::whereBetween('last_login_at', [$startDate, $endDate])->count();
    }

    /**
     * Get certificates issued count
     */
    protected function getCertificatesIssuedCount(Carbon $startDate, Carbon $endDate): int
    {
        return DB::table('certificates')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get average completion rate
     */
    protected function getAverageCompletionRate(): float
    {
        $totalEnrollments = Enrollment::count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();

        return $totalEnrollments > 0 ? ($completedEnrollments / $totalEnrollments) * 100 : 0;
    }

    /**
     * Get total learning hours
     */
    protected function getTotalLearningHours(Carbon $startDate, Carbon $endDate): float
    {
        return DB::table('lesson_progress')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('time_spent') / 60; // Convert to hours
    }

    /**
     * Get enrollment trend
     */
    protected function getEnrollmentTrend(Course $course, Carbon $startDate, Carbon $endDate): array
    {
        $enrollments = $course->enrollments()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $enrollments->pluck('count', 'date')->toArray();
    }

    /**
     * Get average completion time
     */
    protected function getAverageCompletionTime(Course $course): ?float
    {
        $completedEnrollments = $course->enrollments()
            ->where('status', 'completed')
            ->whereNotNull('completion_date')
            ->get();

        if ($completedEnrollments->isEmpty()) {
            return null;
        }

        $totalDays = $completedEnrollments->sum(function ($enrollment) {
            return $enrollment->created_at->diffInDays($enrollment->completion_date);
        });

        return $totalDays / $completedEnrollments->count();
    }

    /**
     * Get overdue count
     */
    protected function getOverdueCount(Department $department): int
    {
        $userIds = $department->users->pluck('id');

        return Enrollment::whereIn('user_id', $userIds)
            ->where('status', 'active')
            ->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->count();
    }

    /**
     * Determine event category
     */
    protected function determineCategory(string $eventType): string
    {
        $categories = [
            'course_view' => 'course',
            'course_enrollment' => 'course',
            'course_completion' => 'course',
            'lesson_view' => 'content',
            'lesson_completion' => 'content',
            'assessment_start' => 'assessment',
            'assessment_submit' => 'assessment',
            'certificate_issued' => 'certificate',
            'login' => 'auth',
            'logout' => 'auth',
        ];

        return $categories[$eventType] ?? 'general';
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(?string $type = null, ?int $id = null): void
    {
        if ($type && $id) {
            Cache::forget("{$type}_analytics_{$id}_*");
        } else {
            Cache::flush();
        }
    }
}
