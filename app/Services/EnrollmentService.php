<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Department;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EnrollmentService
{
    public function __construct(
        protected EnrollmentRepositoryInterface $enrollmentRepository,
        protected CourseRepositoryInterface $courseRepository
    ) {}

    /**
     * Enroll a user in a course
     */
    public function enroll(
        User $user,
        Course $course,
        string $enrollmentType = 'self',
        ?User $enrolledBy = null,
        ?Carbon $deadline = null
    ): Enrollment {
        // Check if already enrolled
        if ($this->enrollmentRepository->isEnrolled($user, $course)) {
            throw new \Exception('User is already enrolled in this course.');
        }

        // Check prerequisites
        if (!$this->checkPrerequisites($user, $course)) {
            throw new \Exception('User has not completed the required prerequisite courses.');
        }

        // Create enrollment
        $enrollmentData = [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => $enrollmentType,
            'enrolled_by' => $enrolledBy?->id,
            'enrollment_date' => now(),
            'deadline' => $deadline,
            'status' => 'active',
            'progress_percentage' => 0,
        ];

        return $this->enrollmentRepository->create($enrollmentData);
    }

    /**
     * Unenroll a user from a course
     */
    public function unenroll(User $user, Course $course): bool
    {
        $enrollment = $this->enrollmentRepository->findByUserAndCourse($user, $course);

        if (!$enrollment) {
            throw new \Exception('User is not enrolled in this course.');
        }

        if ($enrollment->enrollment_type === 'mandatory') {
            throw new \Exception('Cannot unenroll from mandatory courses.');
        }

        return $this->enrollmentRepository->delete($enrollment);
    }

    /**
     * Check if user has completed prerequisites for a course
     */
    public function checkPrerequisites(User $user, Course $course): bool
    {
        if (!$course->hasPrerequisites()) {
            return true;
        }

        $prerequisites = $course->prerequisites;
        
        foreach ($prerequisites as $prerequisiteId) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $prerequisiteId)
                ->where('status', 'completed')
                ->first();

            if (!$enrollment) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing prerequisites for a user and course
     */
    public function getMissingPrerequisites(User $user, Course $course): Collection
    {
        if (!$course->hasPrerequisites()) {
            return collect();
        }

        $prerequisites = $course->prerequisites;
        $missingPrerequisites = [];

        foreach ($prerequisites as $prerequisiteId) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $prerequisiteId)
                ->where('status', 'completed')
                ->first();

            if (!$enrollment) {
                $prerequisiteCourse = Course::find($prerequisiteId);
                if ($prerequisiteCourse) {
                    $missingPrerequisites[] = $prerequisiteCourse;
                }
            }
        }

        return collect($missingPrerequisites);
    }

    /**
     * Auto-enroll users in mandatory courses
     */
    public function autoEnrollMandatoryCourses(?User $user = null, ?Course $course = null): int
    {
        $enrolledCount = 0;

        if ($course && $course->is_mandatory) {
            // Enroll all eligible users in this mandatory course
            $users = $user ? collect([$user]) : User::where('role', 'employee')->get();
            
            foreach ($users as $targetUser) {
                try {
                    if (!$this->enrollmentRepository->isEnrolled($targetUser, $course)) {
                        $this->enroll(
                            $targetUser,
                            $course,
                            'mandatory',
                            null,
                            $this->calculateMandatoryDeadline($course)
                        );
                        $enrolledCount++;
                    }
                } catch (\Exception $e) {
                    // Skip users who don't meet prerequisites
                    continue;
                }
            }
        } elseif ($user) {
            // Enroll this user in all mandatory courses
            $mandatoryCourses = Course::mandatory()->published()->get();
            
            foreach ($mandatoryCourses as $mandatoryCourse) {
                try {
                    if (!$this->enrollmentRepository->isEnrolled($user, $mandatoryCourse)) {
                        $this->enroll(
                            $user,
                            $mandatoryCourse,
                            'mandatory',
                            null,
                            $this->calculateMandatoryDeadline($mandatoryCourse)
                        );
                        $enrolledCount++;
                    }
                } catch (\Exception $e) {
                    // Skip courses where prerequisites aren't met
                    continue;
                }
            }
        }

        return $enrolledCount;
    }

    /**
     * Bulk enroll users in a course
     */
    public function bulkEnroll(
        Collection $users,
        Course $course,
        string $enrollmentType = 'assigned',
        ?User $enrolledBy = null,
        ?Carbon $deadline = null
    ): array {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($users as $user) {
            try {
                $enrollment = $this->enroll($user, $course, $enrollmentType, $enrolledBy, $deadline);
                $results['success'][] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'enrollment_id' => $enrollment->id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Enroll all users in a department
     */
    public function enrollDepartment(
        Department $department,
        Course $course,
        string $enrollmentType = 'assigned',
        ?User $enrolledBy = null,
        ?Carbon $deadline = null
    ): array {
        $users = User::where('department_id', $department->id)->get();
        return $this->bulkEnroll($users, $course, $enrollmentType, $enrolledBy, $deadline);
    }

    /**
     * Update enrollment status
     */
    public function updateStatus(Enrollment $enrollment, string $status): Enrollment
    {
        $validStatuses = ['active', 'completed', 'suspended', 'expired'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid enrollment status.');
        }

        $enrollment = $this->enrollmentRepository->update($enrollment, ['status' => $status]);
        
        // Dispatch CourseCompleted event if status is completed
        if ($status === 'completed') {
            event(new \App\Events\CourseCompleted($enrollment));
        }
        
        return $enrollment;
    }

    /**
     * Update enrollment deadline
     */
    public function updateDeadline(Enrollment $enrollment, ?Carbon $deadline): Enrollment
    {
        return $this->enrollmentRepository->update($enrollment, ['deadline' => $deadline]);
    }

    /**
     * Get enrollments with upcoming deadlines
     */
    public function getUpcomingDeadlines(User $user, int $days = 7): Collection
    {
        return Enrollment::where('user_id', $user->id)
            ->upcomingDeadline($days)
            ->with(['course'])
            ->get();
    }

    /**
     * Get overdue enrollments
     */
    public function getOverdueEnrollments(User $user): Collection
    {
        return Enrollment::where('user_id', $user->id)
            ->overdue()
            ->with(['course'])
            ->get();
    }

    /**
     * Calculate deadline for mandatory courses
     */
    protected function calculateMandatoryDeadline(Course $course): Carbon
    {
        // Default: 30 days from enrollment
        $defaultDays = 30;
        
        // If course has estimated duration, use that + buffer
        if ($course->estimated_duration) {
            $durationInDays = ceil($course->estimated_duration / 60 / 24); // Convert minutes to days
            $bufferDays = max(7, $durationInDays * 0.5); // 50% buffer, minimum 7 days
            $totalDays = $durationInDays + $bufferDays;
            
            return now()->addDays($totalDays);
        }

        return now()->addDays($defaultDays);
    }

    /**
     * Check and update expired enrollments
     */
    public function checkExpiredEnrollments(): int
    {
        $expiredCount = 0;
        
        $expiredEnrollments = Enrollment::where('deadline', '<', now())
            ->where('status', 'active')
            ->get();

        foreach ($expiredEnrollments as $enrollment) {
            $this->updateStatus($enrollment, 'expired');
            $expiredCount++;
        }

        return $expiredCount;
    }

    /**
     * Get enrollment statistics for a user
     */
    public function getUserStatistics(User $user): array
    {
        $enrollments = $this->enrollmentRepository->getByUser($user);

        return [
            'total' => $enrollments->count(),
            'active' => $enrollments->where('status', 'active')->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'overdue' => $enrollments->filter(fn($e) => $e->isOverdue())->count(),
            'average_progress' => $enrollments->where('status', 'active')->avg('progress_percentage') ?? 0,
        ];
    }

    /**
     * Get enrollment statistics for a course
     */
    public function getCourseStatistics(Course $course): array
    {
        $enrollments = $this->enrollmentRepository->getByCourse($course);

        return [
            'total' => $enrollments->count(),
            'active' => $enrollments->where('status', 'active')->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'completion_rate' => $enrollments->count() > 0 
                ? ($enrollments->where('status', 'completed')->count() / $enrollments->count()) * 100 
                : 0,
            'average_progress' => $enrollments->where('status', 'active')->avg('progress_percentage') ?? 0,
        ];
    }
}
