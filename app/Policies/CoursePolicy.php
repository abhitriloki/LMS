<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Determine if the user can view any courses.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view courses
    }

    /**
     * Determine if the user can view the course.
     */
    public function view(User $user, Course $course): bool
    {
        // Users can view published courses or courses they created
        return $course->is_published || $course->created_by === $user->id;
    }

    /**
     * Determine if the user can create courses.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin', 'instructor']);
    }

    /**
     * Determine if the user can update the course.
     */
    public function update(User $user, Course $course): bool
    {
        // Super admin and HR admin can update any course
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can only update their own courses
        return $user->role === 'instructor' && $course->created_by === $user->id;
    }

    /**
     * Determine if the user can delete the course.
     */
    public function delete(User $user, Course $course): bool
    {
        // Super admin and HR admin can delete any course
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can only delete their own courses
        return $user->role === 'instructor' && $course->created_by === $user->id;
    }

    /**
     * Determine if the user can publish the course.
     */
    public function publish(User $user, Course $course): bool
    {
        // Super admin and HR admin can publish any course
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can publish their own courses
        return $user->role === 'instructor' && $course->created_by === $user->id;
    }

    /**
     * Determine if the user can clone the course.
     */
    public function clone(User $user, Course $course): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin', 'instructor']);
    }

    /**
     * Determine if the user can manage course modules and lessons.
     */
    public function manageContent(User $user, Course $course): bool
    {
        // Super admin and HR admin can manage any course content
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can only manage their own course content
        return $user->role === 'instructor' && $course->created_by === $user->id;
    }
}
