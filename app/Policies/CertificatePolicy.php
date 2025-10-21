<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /**
     * Determine whether the user can view any certificates.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine whether the user can view the certificate.
     */
    public function view(User $user, Certificate $certificate): bool
    {
        return $user->id === $certificate->user_id || 
               in_array($user->role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine whether the user can create certificates.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin', 'instructor']);
    }

    /**
     * Determine whether the user can delete the certificate.
     */
    public function delete(User $user, Certificate $certificate): bool
    {
        return in_array($user->role, ['super_admin', 'hr_admin']);
    }
}
