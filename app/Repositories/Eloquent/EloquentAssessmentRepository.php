<?php

namespace App\Repositories\Eloquent;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentAssessmentRepository implements AssessmentRepositoryInterface
{
    /**
     * Create a new assessment
     */
    public function create(array $data): Assessment
    {
        return Assessment::create($data);
    }

    /**
     * Update an existing assessment
     */
    public function update(Assessment $assessment, array $data): Assessment
    {
        $assessment->update($data);
        return $assessment->fresh();
    }

    /**
     * Find assessment by ID
     */
    public function find(int $id): ?Assessment
    {
        return Assessment::with(['course', 'creator'])->find($id);
    }

    /**
     * Find assessment with questions
     */
    public function findWithQuestions(int $id): ?Assessment
    {
        return Assessment::with([
            'course',
            'creator',
            'questions' => function ($query) {
                $query->orderBy('order_index');
            },
            'questions.options'
        ])->find($id);
    }

    /**
     * Get assessments by course
     */
    public function getByCourse(Course $course): Collection
    {
        return Assessment::where('course_id', $course->id)
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get published assessments
     */
    public function getPublished(): Collection
    {
        return Assessment::where('is_published', true)
            ->with(['course', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get assessments created by user
     */
    public function getByCreator(User $user): Collection
    {
        return Assessment::where('created_by', $user->id)
            ->with(['course'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete an assessment
     */
    public function delete(Assessment $assessment): bool
    {
        return $assessment->delete();
    }

    /**
     * Publish an assessment
     */
    public function publish(Assessment $assessment): Assessment
    {
        $assessment->update(['is_published' => true]);
        return $assessment->fresh();
    }

    /**
     * Unpublish an assessment
     */
    public function unpublish(Assessment $assessment): Assessment
    {
        $assessment->update(['is_published' => false]);
        return $assessment->fresh();
    }

    /**
     * Get all assessments with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Assessment::with(['course', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Search assessments with filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Assessment::query()
            ->with(['course', 'creator']);

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        $perPage = $filters['per_page'] ?? 15;
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }
}
