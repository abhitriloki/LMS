<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * Create a new user
     */
    public function create(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return User::create($data);
    }

    /**
     * Update an existing user
     */
    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user->fresh();
    }

    /**
     * Find user by ID
     */
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        $allowedFields = ['name', 'phone', 'bio', 'avatar', 'position', 'preferences'];
        $profileData = array_intersect_key($data, array_flip($allowedFields));
        
        $user->update($profileData);
        return $user->fresh();
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, string $role): void
    {
        $user->update(['role' => $role]);
    }

    /**
     * Get users by department
     */
    public function getUsersByDepartment(int $departmentId): Collection
    {
        return User::where('department_id', $departmentId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search users with filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with('department');

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        $perPage = $filters['per_page'] ?? 15;
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Get all users with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('department')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Delete a user
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): Collection
    {
        return User::where('role', $role)
            ->orderBy('name')
            ->get();
    }
}
