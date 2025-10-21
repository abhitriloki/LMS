<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the model
     */
    public function view(User $user, User $model): bool
    {
        // Admins can view any user, users can view themselves
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine if the user can create users
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update the model
     */
    public function update(User $user, User $model): bool
    {
        // Admins can update any user, users can update themselves
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Determine if the user can delete the model
     */
    public function delete(User $user, User $model): bool
    {
        // Super admins can delete any user except themselves
        // Regular admins cannot delete super admins
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Regular admins cannot delete super admins
        return $user->isAdmin() && !$model->isSuperAdmin();
    }
}
