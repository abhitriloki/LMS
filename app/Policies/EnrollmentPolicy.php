<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Determine if the user can view any enrollments.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view enrollments
    }

    /**
     * Determine if the user can view the enrollment.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        // Admins can view all enrollments
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can view enrollments for their courses
        if ($user->role === 'instructor' && $enrollment->course->created_by === $user->id) {
            return true;
        }

        // Users can view their own enrollments
        return $enrollment->user_id === $user->id;
    }

    /**
     * Determine if the user can enroll in a course.
     */
    public function enroll(User $user, Course $course): bool
    {
        // Course must be published
        if (!$course->is_published) {
            return false;
        }

        // Check if user is already enrolled
        $existingEnrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if ($existingEnrollment) {
            return false;
        }

        // Check prerequisites
        if (!empty($course->prerequisites)) {
            foreach ($course->prerequisites as $prerequisiteId) {
                $completed = $user->enrollments()
                    ->where('course_id', $prerequisiteId)
                    ->where('status', 'completed')
                    ->exists();

                if (!$completed) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if the user can unenroll from a course.
     */
    public function unenroll(User $user, Enrollment $enrollment): bool
    {
        // Admins can unenroll anyone
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Users can unenroll themselves from non-mandatory courses
        if ($enrollment->user_id === $user->id && $enrollment->enrollment_type !== 'mandatory') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create bulk enrollments.
     */
    public function bulkEnroll(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine if the user can update enrollment status.
     */
    public function updateStatus(User $user, Enrollment $enrollment): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine if the user can view enrollment progress.
     */
    public function viewProgress(User $user, Enrollment $enrollment): bool
    {
        // Admins can view all progress
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can view progress for their courses
        if ($user->role === 'instructor' && $enrollment->course->created_by === $user->id) {
            return true;
        }

        // Users can view their own progress
        return $enrollment->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the enrollment.
     */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        // Only admins can delete enrollments
        return in_array($user->role, ['super_admin', 'hr_admin']);
    }
}
