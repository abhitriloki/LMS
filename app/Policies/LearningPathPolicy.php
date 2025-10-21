<?php

namespace App\Policies;

use App\Models\AILearningPath;
use App\Models\User;

class LearningPathPolicy
{
    /**
     * Determine if the user can view any learning paths
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the learning path
     */
    public function view(User $user, AILearningPath $learningPath): bool
    {
        // Users can view their own learning paths
        // Admins can view all learning paths
        return $user->id === $learningPath->user_id || $user->role === 'super_admin' || $user->role === 'hr_admin';
    }

    /**
     * Determine if the user can create learning paths
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the learning path
     */
    public function update(User $user, AILearningPath $learningPath): bool
    {
        // Users can update their own learning paths
        return $user->id === $learningPath->user_id;
    }

    /**
     * Determine if the user can delete the learning path
     */
    public function delete(User $user, AILearningPath $learningPath): bool
    {
        // Users can delete their own learning paths
        // Admins can delete any learning path
        return $user->id === $learningPath->user_id || $user->role === 'super_admin' || $user->role === 'hr_admin';
    }
}
