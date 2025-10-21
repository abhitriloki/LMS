<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Services\ModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModuleController extends Controller
{
    public function __construct(
        protected ModuleService $moduleService
    ) {}

    /**
     * Store a newly created module
     */
    public function store(Request $request, Course $course)
    {
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer|min:0',
        ]);

        $module = $this->moduleService->createModule($course, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Module created successfully.',
                'module' => $module->load('lessons')
            ], 201);
        }

        return redirect()
            ->back()
            ->with('success', 'Module created successfully.');
    }

    /**
     * Show the form for editing the specified module
     */
    public function edit(CourseModule $module)
    {
        Gate::authorize('update', $module->course);

        $module->load(['course', 'lessons']);

        return view('admin.modules.edit', compact('module'));
    }

    /**
     * Update the specified module
     */
    public function update(Request $request, CourseModule $module)
    {
        Gate::authorize('update', $module->course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer|min:0',
        ]);

        $module = $this->moduleService->updateModule($module, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Module updated successfully.',
                'module' => $module
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Module updated successfully.');
    }

    /**
     * Remove the specified module
     */
    public function destroy(CourseModule $module)
    {
        Gate::authorize('update', $module->course);

        $this->moduleService->deleteModule($module);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Module deleted successfully.'
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Module deleted successfully.');
    }

    /**
     * Reorder modules
     */
    public function reorder(Request $request, Course $course)
    {
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:course_modules,id',
        ]);

        $this->moduleService->reorderModules($course, $validated['order']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Modules reordered successfully.'
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Modules reordered successfully.');
    }

    /**
     * Duplicate a module
     */
    public function duplicate(CourseModule $module)
    {
        Gate::authorize('update', $module->course);

        $duplicateModule = $this->moduleService->duplicateModule($module);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Module duplicated successfully.',
                'module' => $duplicateModule
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Module duplicated successfully.');
    }
}
