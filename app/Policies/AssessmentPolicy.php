<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    /**
     * Determine if the user can view any assessments.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view assessments
    }

    /**
     * Determine if the user can view the assessment.
     */
    public function view(User $user, Assessment $assessment): bool
    {
        // Users can view published assessments or assessments they created
        return $assessment->is_published || $assessment->created_by === $user->id;
    }

    /**
     * Determine if the user can create assessments.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin', 'instructor']);
    }

    /**
     * Determine if the user can update the assessment.
     */
    public function update(User $user, Assessment $assessment): bool
    {
        // Super admin and HR admin can update any assessment
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can only update their own assessments
        return $user->role === 'instructor' && $assessment->created_by === $user->id;
    }

    /**
     * Determine if the user can delete the assessment.
     */
    public function delete(User $user, Assessment $assessment): bool
    {
        // Super admin and HR admin can delete any assessment
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can only delete their own assessments
        return $user->role === 'instructor' && $assessment->created_by === $user->id;
    }

    /**
     * Determine if the user can publish the assessment.
     */
    public function publish(User $user, Assessment $assessment): bool
    {
        // Super admin and HR admin can publish any assessment
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can publish their own assessments
        return $user->role === 'instructor' && $assessment->created_by === $user->id;
    }

    /**
     * Determine if the user can take the assessment.
     */
    public function take(User $user, Assessment $assessment): bool
    {
        // Only published assessments can be taken
        if (!$assessment->is_published) {
            return false;
        }

        // Check if user is enrolled in the course
        $enrollment = $user->enrollments()
            ->where('course_id', $assessment->course_id)
            ->where('status', 'active')
            ->first();

        return $enrollment !== null;
    }

    /**
     * Determine if the user can view assessment results.
     */
    public function viewResults(User $user, Assessment $assessment): bool
    {
        // Admins and instructors can view all results
        if (in_array($user->role, ['super_admin', 'hr_admin', 'instructor'])) {
            return true;
        }

        // Students can view their own results if configured
        return $assessment->show_results;
    }

    /**
     * Determine if the user can grade assessment attempts.
     */
    public function grade(User $user, Assessment $assessment): bool
    {
        // Super admin and HR admin can grade any assessment
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Instructors can grade their own assessments
        return $user->role === 'instructor' && $assessment->created_by === $user->id;
    }
}
