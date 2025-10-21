<?php

namespace App\Repositories\Eloquent;

use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class EloquentCourseRepository implements CourseRepositoryInterface
{
    /**
     * Create a new course
     */
    public function create(array $data): Course
    {
        if (!isset($data['slug']) && isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        return Course::create($data);
    }

    /**
     * Update an existing course
     */
    public function update(Course $course, array $data): Course
    {
        if (isset($data['title']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $course->update($data);
        return $course->fresh();
    }

    /**
     * Find course by ID
     */
    public function find(int $id): ?Course
    {
        return Course::with(['category', 'creator'])->find($id);
    }

    /**
     * Find course with modules and lessons
     */
    public function findWithModules(int $id): ?Course
    {
        return Course::with([
            'category',
            'creator',
            'modules' => function ($query) {
                $query->orderBy('order_index');
            },
            'modules.lessons' => function ($query) {
                $query->orderBy('order_index');
            }
        ])->find($id);
    }

    /**
     * Search courses with filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Course::query()
            ->with(['category', 'creator']);

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }

        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        if (isset($filters['is_mandatory'])) {
            $query->where('is_mandatory', $filters['is_mandatory']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Get all published courses
     */
    public function getPublished(): Collection
    {
        return Course::where('is_published', true)
            ->with(['category', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get mandatory courses for a user
     */
    public function getMandatoryForUser(User $user): Collection
    {
        return Course::where('is_mandatory', true)
            ->where('is_published', true)
            ->with(['category', 'creator'])
            ->get();
    }

    /**
     * Get courses by category
     */
    public function getByCategory(int $categoryId): Collection
    {
        return Course::where('category_id', $categoryId)
            ->where('is_published', true)
            ->with(['category', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get featured courses
     */
    public function getFeatured(): Collection
    {
        return Course::where('is_featured', true)
            ->where('is_published', true)
            ->with(['category', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get courses created by user
     */
    public function getByCreator(User $user): Collection
    {
        return Course::where('created_by', $user->id)
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete a course
     */
    public function delete(Course $course): bool
    {
        return $course->delete();
    }

    /**
     * Publish a course
     */
    public function publish(Course $course): Course
    {
        $course->update(['is_published' => true]);
        return $course->fresh();
    }

    /**
     * Unpublish a course
     */
    public function unpublish(Course $course): Course
    {
        $course->update(['is_published' => false]);
        return $course->fresh();
    }

    /**
     * Get all courses with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Course::with(['category', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
