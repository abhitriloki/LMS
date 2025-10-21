<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\CourseService;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    public function __construct(
        protected CourseService $courseService,
        protected CourseRepositoryInterface $courseRepository
    ) {}

    /**
     * Display a listing of courses
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'difficulty_level' => $request->input('difficulty_level'),
            'is_published' => $request->input('is_published'),
            'is_mandatory' => $request->input('is_mandatory'),
            'is_featured' => $request->input('is_featured'),
            'per_page' => $request->input('per_page', 15),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        // Filter out null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $courses = $this->courseRepository->search($filters);
        $categories = CourseCategory::orderBy('name')->get();

        return view('admin.courses.index', compact('courses', 'categories', 'filters'));
    }

    /**
     * Show the form for creating a new course
     */
    public function create()
    {
        Gate::authorize('create', Course::class);

        $categories = CourseCategory::orderBy('name')->get();
        $courses = Course::where('is_published', true)->orderBy('title')->get();

        return view('admin.courses.create', compact('categories', 'courses'));
    }

    /**
     * Store a newly created course
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Course::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'required|string',
            'category_id' => 'required|exists:course_categories,id',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'estimated_duration' => 'nullable|integer|min:0',
            'thumbnail' => 'nullable|image|max:2048',
            'preview_video' => 'nullable|url',
            'price' => 'nullable|numeric|min:0',
            'is_mandatory' => 'boolean',
            'is_featured' => 'boolean',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:courses,id',
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'string',
            'target_audience' => 'nullable|string',
            'language' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        }

        $course = $this->courseService->createCourse($validated, $request->user());

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified course
     */
    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        $course->load(['category', 'creator', 'modules.lessons']);

        return view('admin.courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified course
     */
    public function edit(Course $course)
    {
        Gate::authorize('update', $course);

        $categories = CourseCategory::orderBy('name')->get();
        $courses = Course::where('is_published', true)
            ->where('id', '!=', $course->id)
            ->orderBy('title')
            ->get();

        return view('admin.courses.edit', compact('course', 'categories', 'courses'));
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, Course $course)
    {
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'required|string',
            'category_id' => 'required|exists:course_categories,id',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'estimated_duration' => 'nullable|integer|min:0',
            'thumbnail' => 'nullable|image|max:2048',
            'preview_video' => 'nullable|url',
            'price' => 'nullable|numeric|min:0',
            'is_mandatory' => 'boolean',
            'is_featured' => 'boolean',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:courses,id',
            'learning_objectives' => 'nullable|array',
            'learning_objectives.*' => 'string',
            'target_audience' => 'nullable|string',
            'language' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        }

        $course = $this->courseService->updateCourse($course, $validated);

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified course
     */
    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);

        try {
            $this->courseService->deleteCourse($course);

            return redirect()
                ->route('admin.courses.index')
                ->with('success', 'Course deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Publish a course
     */
    public function publish(Course $course)
    {
        Gate::authorize('update', $course);

        try {
            $this->courseService->publishCourse($course);

            return redirect()
                ->back()
                ->with('success', 'Course published successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Unpublish a course
     */
    public function unpublish(Course $course)
    {
        Gate::authorize('update', $course);

        $this->courseService->unpublishCourse($course);

        return redirect()
            ->back()
            ->with('success', 'Course unpublished successfully.');
    }

    /**
     * Clone a course
     */
    public function clone(Request $request, Course $course)
    {
        Gate::authorize('create', Course::class);

        $clonedCourse = $this->courseService->cloneCourse($course);

        return redirect()
            ->route('admin.courses.edit', $clonedCourse)
            ->with('success', 'Course cloned successfully. You can now edit the cloned course.');
    }

    /**
     * Show course builder interface
     */
    public function builder(Course $course)
    {
        Gate::authorize('update', $course);

        $course->load(['modules.lessons']);

        return view('admin.courses.builder', compact('course'));
    }
}
