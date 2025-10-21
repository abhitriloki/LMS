<?php

namespace App\Repositories\Contracts;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface EnrollmentRepositoryInterface
{
    /**
     * Create a new enrollment
     */
    public function create(array $data): Enrollment;

    /**
     * Update an existing enrollment
     */
    public function update(Enrollment $enrollment, array $data): Enrollment;

    /**
     * Find enrollment by ID
     */
    public function find(int $id): ?Enrollment;

    /**
     * Find enrollment by user and course
     */
    public function findByUserAndCourse(User $user, Course $course): ?Enrollment;

    /**
     * Get enrollments by user
     */
    public function getByUser(User $user): Collection;

    /**
     * Get enrollments by course
     */
    public function getByCourse(Course $course): Collection;

    /**
     * Get active enrollments for user
     */
    public function getActiveByUser(User $user): Collection;

    /**
     * Get completed enrollments for user
     */
    public function getCompletedByUser(User $user): Collection;

    /**
     * Calculate progress percentage
     */
    public function calculateProgress(Enrollment $enrollment): float;

    /**
     * Update progress percentage
     */
    public function updateProgress(Enrollment $enrollment): Enrollment;

    /**
     * Mark enrollment as completed
     */
    public function markAsCompleted(Enrollment $enrollment): Enrollment;

    /**
     * Delete an enrollment
     */
    public function delete(Enrollment $enrollment): bool;

    /**
     * Get enrollments with filters
     */
    public function search(array $filters): LengthAwarePaginator;

    /**
     * Get all enrollments with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Check if user is enrolled in course
     */
    public function isEnrolled(User $user, Course $course): bool;
}
