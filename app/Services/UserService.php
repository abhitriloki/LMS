<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        // Handle avatar upload if present
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            $data['avatar'] = $this->uploadAvatar($data['avatar']);
        }

        return $this->userRepository->create($data);
    }

    /**
     * Update an existing user
     */
    public function updateUser(User $user, array $data): User
    {
        // Handle avatar upload if present
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            // Delete old avatar if exists
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = $this->uploadAvatar($data['avatar']);
        }

        return $this->userRepository->update($user, $data);
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        // Handle avatar upload if present
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            // Delete old avatar if exists
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = $this->uploadAvatar($data['avatar']);
        }

        return $this->userRepository->updateProfile($user, $data);
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('avatars', $filename, 'public');
        
        return $path;
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    /**
     * Assign department to user
     */
    public function assignDepartment(User $user, int $departmentId): User
    {
        return $this->userRepository->update($user, ['department_id' => $departmentId]);
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, string $role): void
    {
        $this->userRepository->assignRole($user, $role);
    }

    /**
     * Search users with filters
     */
    public function searchUsers(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->search($filters);
    }

    /**
     * Get users by department
     */
    public function getUsersByDepartment(int $departmentId)
    {
        return $this->userRepository->getUsersByDepartment($departmentId);
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role)
    {
        return $this->userRepository->getUsersByRole($role);
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user): bool
    {
        // Delete avatar if exists
        if ($user->avatar) {
            $this->deleteAvatar($user->avatar);
        }

        return $this->userRepository->delete($user);
    }

    /**
     * Get paginated users
     */
    public function getPaginatedUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    /**
     * Find user by ID
     */
    public function findUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrl(?string $avatarPath): string
    {
        if (!$avatarPath) {
            return $this->getDefaultAvatarUrl();
        }

        return Storage::disk('public')->url($avatarPath);
    }

    /**
     * Get default avatar URL
     */
    public function getDefaultAvatarUrl(): string
    {
        return asset('images/default-avatar.png');
    }
}
