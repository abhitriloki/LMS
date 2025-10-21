<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(
        protected CourseRepositoryInterface $courseRepository
    ) {}

    /**
     * Display a listing of courses.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'category_id',
            'difficulty_level',
            'is_featured',
            'is_mandatory',
            'tags',
        ]);

        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = Course::with(['category', 'creator'])
            ->where('is_published', true);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_mandatory'])) {
            $query->where('is_mandatory', filter_var($filters['is_mandatory'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : explode(',', $filters['tags']);
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', trim($tag));
                }
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        $courses = $query->paginate($perPage);

        return CourseResource::collection($courses);
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        if (!$course->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
            ], 404);
        }

        $course->load(['category', 'creator', 'modules.lessons']);

        return response()->json([
            'success' => true,
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * Get course modules and lessons.
     */
    public function modules(Course $course)
    {
        if (!$course->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
            ], 404);
        }

        $modules = $course->modules()
            ->with('lessons')
            ->orderBy('order_index')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $modules,
        ]);
    }
}
