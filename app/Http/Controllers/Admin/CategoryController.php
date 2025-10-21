<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of categories
     */
    public function index()
    {
        Gate::authorize('viewAny', CourseCategory::class);

        $categories = $this->categoryService->getCategoryTree();
        $flatCategories = $this->categoryService->getAllCategories();

        return view('admin.categories.index', compact('categories', 'flatCategories'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        Gate::authorize('create', CourseCategory::class);

        $categories = $this->categoryService->getAllCategories();

        return view('admin.categories.create', compact('categories'));
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        Gate::authorize('create', CourseCategory::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:course_categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:course_categories,id',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'order_index' => 'nullable|integer|min:0',
        ]);

        try {
            $category = $this->categoryService->createCategory($validated);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified category
     */
    public function show(CourseCategory $category)
    {
        Gate::authorize('view', $category);

        $category = $this->categoryService->getCategoryWithRelations($category->id);

        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit(CourseCategory $category)
    {
        Gate::authorize('update', $category);

        $categories = $this->categoryService->getAllCategories()
            ->reject(fn($cat) => $cat->id === $category->id);

        return view('admin.categories.edit', compact('category', 'categories'));
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, CourseCategory $category)
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:course_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:course_categories,id',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'order_index' => 'nullable|integer|min:0',
        ]);

        try {
            $this->categoryService->updateCategory($category, $validated);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(CourseCategory $category)
    {
        Gate::authorize('delete', $category);

        try {
            $this->categoryService->deleteCategory($category);

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category deleted successfully.');
        } catch (\RuntimeException $e) {
            return back()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request)
    {
        Gate::authorize('update', CourseCategory::class);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|exists:course_categories,id',
        ]);

        try {
            $this->categoryService->reorderCategories($validated['order']);

            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories: ' . $e->getMessage(),
            ], 500);
        }
    }
}
