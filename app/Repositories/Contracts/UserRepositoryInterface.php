<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * Create a new user
     */
    public function create(array $data): User;

    /**
     * Update an existing user
     */
    public function update(User $user, array $data): User;

    /**
     * Find user by ID
     */
    public function find(int $id): ?User;

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User;

    /**
     * Assign role to user
     */
    public function assignRole(User $user, string $role): void;

    /**
     * Get users by department
     */
    public function getUsersByDepartment(int $departmentId): Collection;

    /**
     * Search users with filters
     */
    public function search(array $filters): LengthAwarePaginator;

    /**
     * Get all users with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Delete a user
     */
    public function delete(User $user): bool;

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): Collection;
}
