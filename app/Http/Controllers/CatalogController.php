<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    /**
     * Display the course catalog
     */
    public function index(Request $request): View
    {
        // Use Scout search if search term is provided
        if ($request->filled('search')) {
            $courses = $this->searchWithScout($request);
        } else {
            $courses = $this->filterCourses($request);
        }

        // Cache categories for 1 hour
        $categories = Cache::remember('catalog.categories', 3600, function () {
            return CourseCategory::with('children')
                ->whereNull('parent_id')
                ->orderBy('order_index')
                ->get();
        });

        // Get available difficulty levels
        $difficultyLevels = ['beginner', 'intermediate', 'advanced'];

        // Cache popular tags for 30 minutes
        $popularTags = Cache::remember('catalog.popular_tags', 1800, function () {
            return $this->getPopularTags();
        });

        return view('catalog.index', compact(
            'courses',
            'categories',
            'difficultyLevels',
            'popularTags'
        ));
    }

    /**
     * Search courses using Laravel Scout
     */
    private function searchWithScout(Request $request)
    {
        $searchQuery = Course::search($request->search);

        // Apply filters
        if ($request->filled('category')) {
            $searchQuery->where('category_id', $request->category);
        }

        if ($request->filled('difficulty')) {
            $searchQuery->where('difficulty_level', $request->difficulty);
        }

        if ($request->filled('tags')) {
            $tags = is_array($request->tags) ? $request->tags : [$request->tags];
            foreach ($tags as $tag) {
                $searchQuery->where('tags', $tag);
            }
        }

        // Get results and load relationships
        $results = $searchQuery->paginate(12)->withQueryString();
        $results->load(['category', 'creator', 'modules']);

        return $results;
    }

    /**
     * Filter courses without search
     */
    private function filterCourses(Request $request)
    {
        $query = Course::query()
            ->with(['category', 'creator', 'modules'])
            ->published();

        // Apply category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Apply difficulty filter
        if ($request->filled('difficulty')) {
            $query->where('difficulty_level', $request->difficulty);
        }

        // Apply tags filter
        if ($request->filled('tags')) {
            $tags = is_array($request->tags) ? $request->tags : [$request->tags];
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'difficulty':
                $query->orderBy('difficulty_level', $sortOrder);
                break;
            case 'duration':
                $query->orderBy('estimated_duration', $sortOrder);
                break;
            case 'popular':
                $query->withCount('enrollments')->orderBy('enrollments_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        return $query->paginate(12)->withQueryString();

        // Get all categories for filter sidebar
        $categories = CourseCategory::with('children')
            ->whereNull('parent_id')
            ->orderBy('order_index')
            ->get();

        // Get available difficulty levels
        $difficultyLevels = ['beginner', 'intermediate', 'advanced'];

        // Get popular tags
        $popularTags = $this->getPopularTags();

        return view('catalog.index', compact(
            'courses',
            'categories',
            'difficultyLevels',
            'popularTags'
        ));
    }

    /**
     * Display a single course detail page
     */
    public function show(Course $course): View
    {
        // Only show published courses
        abort_if(!$course->is_published, 404);

        $course->load([
            'category',
            'creator',
            'modules.lessons',
            'assessments'
        ]);

        // Cache related courses for 1 hour
        $relatedCourses = Cache::remember(
            "course.{$course->id}.related",
            3600,
            function () use ($course) {
                return Course::published()
                    ->where('category_id', $course->category_id)
                    ->where('id', '!=', $course->id)
                    ->limit(4)
                    ->get();
            }
        );

        // Check if user is enrolled
        $isEnrolled = auth()->check() && $course->isEnrolledBy(auth()->user());

        return view('catalog.show', compact('course', 'relatedCourses', 'isEnrolled'));
    }

    /**
     * Get popular tags from published courses
     */
    private function getPopularTags(): array
    {
        $courses = Course::published()
            ->whereNotNull('tags')
            ->get();

        $tagCounts = [];
        foreach ($courses as $course) {
            if (is_array($course->tags)) {
                foreach ($course->tags as $tag) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }

        arsort($tagCounts);
        return array_slice(array_keys($tagCounts), 0, 20);
    }
}
