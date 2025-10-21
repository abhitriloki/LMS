<?php

namespace App\Repositories\Eloquent;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentEnrollmentRepository implements EnrollmentRepositoryInterface
{
    /**
     * Create a new enrollment
     */
    public function create(array $data): Enrollment
    {
        return Enrollment::create($data);
    }

    /**
     * Update an existing enrollment
     */
    public function update(Enrollment $enrollment, array $data): Enrollment
    {
        $enrollment->update($data);
        return $enrollment->fresh();
    }

    /**
     * Find enrollment by ID
     */
    public function find(int $id): ?Enrollment
    {
        return Enrollment::with(['course', 'user'])->find($id);
    }

    /**
     * Find enrollment by user and course
     */
    public function findByUserAndCourse(User $user, Course $course): ?Enrollment
    {
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
    }

    /**
     * Get enrollments by user
     */
    public function getByUser(User $user): Collection
    {
        return Enrollment::where('user_id', $user->id)
            ->with(['course', 'course.category'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get enrollments by course
     */
    public function getByCourse(Course $course): Collection
    {
        return Enrollment::where('course_id', $course->id)
            ->with(['user', 'user.department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active enrollments for user
     */
    public function getActiveByUser(User $user): Collection
    {
        return Enrollment::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['course', 'course.category'])
            ->orderBy('last_accessed_at', 'desc')
            ->get();
    }

    /**
     * Get completed enrollments for user
     */
    public function getCompletedByUser(User $user): Collection
    {
        return Enrollment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['course', 'course.category', 'certificate'])
            ->orderBy('completion_date', 'desc')
            ->get();
    }

    /**
     * Calculate progress percentage
     */
    public function calculateProgress(Enrollment $enrollment): float
    {
        $course = $enrollment->course()->with('modules.lessons')->first();
        
        if (!$course) {
            return 0.0;
        }

        $totalLessons = 0;
        foreach ($course->modules as $module) {
            $totalLessons += $module->lessons->count();
        }

        if ($totalLessons === 0) {
            return 0.0;
        }

        $completedLessons = $enrollment->lessonProgress()
            ->where('is_completed', true)
            ->count();

        return round(($completedLessons / $totalLessons) * 100, 2);
    }

    /**
     * Update progress percentage
     */
    public function updateProgress(Enrollment $enrollment): Enrollment
    {
        $progress = $this->calculateProgress($enrollment);
        $enrollment->update(['progress_percentage' => $progress]);
        
        return $enrollment->fresh();
    }

    /**
     * Mark enrollment as completed
     */
    public function markAsCompleted(Enrollment $enrollment): Enrollment
    {
        $enrollment->update([
            'status' => 'completed',
            'completion_date' => now(),
            'progress_percentage' => 100.00
        ]);

        return $enrollment->fresh();
    }

    /**
     * Delete an enrollment
     */
    public function delete(Enrollment $enrollment): bool
    {
        return $enrollment->delete();
    }

    /**
     * Get enrollments with filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Enrollment::query()
            ->with(['course', 'user', 'user.department']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['enrollment_type'])) {
            $query->where('enrollment_type', $filters['enrollment_type']);
        }

        if (isset($filters['department_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if (isset($filters['deadline_before'])) {
            $query->where('deadline', '<=', $filters['deadline_before']);
        }

        if (isset($filters['deadline_after'])) {
            $query->where('deadline', '>=', $filters['deadline_after']);
        }

        $perPage = $filters['per_page'] ?? 15;
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Get all enrollments with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Enrollment::with(['course', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if user is enrolled in course
     */
    public function isEnrolled(User $user, Course $course): bool
    {
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
    }
}
