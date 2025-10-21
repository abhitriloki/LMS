<?php

namespace App\Policies;

use App\Models\CourseCategory;
use App\Models\User;

class CourseCategoryPolicy
{
    /**
     * Determine if the user can view any categories
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin', 'instructor']);
    }

    /**
     * Determine if the user can view the category
     */
    public function view(User $user, CourseCategory $category): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin', 'instructor']);
    }

    /**
     * Determine if the user can create categories
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine if the user can update the category
     */
    public function update(User $user, CourseCategory $category): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine if the user can delete the category
     */
    public function delete(User $user, CourseCategory $category): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin']);
    }
}
