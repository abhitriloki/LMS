<?php

namespace App\Repositories\Contracts;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AssessmentRepositoryInterface
{
    /**
     * Create a new assessment
     */
    public function create(array $data): Assessment;

    /**
     * Update an existing assessment
     */
    public function update(Assessment $assessment, array $data): Assessment;

    /**
     * Find assessment by ID
     */
    public function find(int $id): ?Assessment;

    /**
     * Find assessment with questions
     */
    public function findWithQuestions(int $id): ?Assessment;

    /**
     * Get assessments by course
     */
    public function getByCourse(Course $course): Collection;

    /**
     * Get published assessments
     */
    public function getPublished(): Collection;

    /**
     * Get assessments created by user
     */
    public function getByCreator(User $user): Collection;

    /**
     * Delete an assessment
     */
    public function delete(Assessment $assessment): bool;

    /**
     * Publish an assessment
     */
    public function publish(Assessment $assessment): Assessment;

    /**
     * Unpublish an assessment
     */
    public function unpublish(Assessment $assessment): Assessment;

    /**
     * Get all assessments with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Search assessments with filters
     */
    public function search(array $filters): LengthAwarePaginator;
}
