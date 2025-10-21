<?php

namespace App\Repositories\Contracts;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CourseRepositoryInterface
{
    /**
     * Create a new course
     */
    public function create(array $data): Course;

    /**
     * Update an existing course
     */
    public function update(Course $course, array $data): Course;

    /**
     * Find course by ID
     */
    public function find(int $id): ?Course;

    /**
     * Find course with modules and lessons
     */
    public function findWithModules(int $id): ?Course;

    /**
     * Search courses with filters
     */
    public function search(array $filters): LengthAwarePaginator;

    /**
     * Get all published courses
     */
    public function getPublished(): Collection;

    /**
     * Get mandatory courses for a user
     */
    public function getMandatoryForUser(User $user): Collection;

    /**
     * Get courses by category
     */
    public function getByCategory(int $categoryId): Collection;

    /**
     * Get featured courses
     */
    public function getFeatured(): Collection;

    /**
     * Get courses created by user
     */
    public function getByCreator(User $user): Collection;

    /**
     * Delete a course
     */
    public function delete(Course $course): bool;

    /**
     * Publish a course
     */
    public function publish(Course $course): Course;

    /**
     * Unpublish a course
     */
    public function unpublish(Course $course): Course;

    /**
     * Get all courses with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
