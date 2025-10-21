<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Services\LessonService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonController extends Controller
{
    public function __construct(
        protected LessonService $lessonService,
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Store a newly created lesson
     */
    public function store(Request $request, CourseModule $module)
    {
        Gate::authorize('update', $module->course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:video,pdf,scorm,presentation,text',
            'content_path' => 'nullable|string',
            'content_url' => 'nullable|url',
            'duration' => 'nullable|integer|min:0',
            'order_index' => 'nullable|integer|min:0',
            'is_downloadable' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $lesson = $this->lessonService->createLesson($module, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully.',
                'lesson' => $lesson
            ], 201);
        }

        return redirect()
            ->back()
            ->with('success', 'Lesson created successfully.');
    }

    /**
     * Show the form for editing the specified lesson
     */
    public function edit(CourseLesson $lesson)
    {
        Gate::authorize('update', $lesson->module->course);

        $lesson->load(['module.course']);

        return view('admin.lessons.edit', compact('lesson'));
    }

    /**
     * Update the specified lesson
     */
    public function update(Request $request, CourseLesson $lesson)
    {
        Gate::authorize('update', $lesson->module->course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:video,pdf,scorm,presentation,text',
            'content_path' => 'nullable|string',
            'content_url' => 'nullable|url',
            'duration' => 'nullable|integer|min:0',
            'order_index' => 'nullable|integer|min:0',
            'is_downloadable' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $lesson = $this->lessonService->updateLesson($lesson, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully.',
                'lesson' => $lesson
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Lesson updated successfully.');
    }

    /**
     * Remove the specified lesson
     */
    public function destroy(CourseLesson $lesson)
    {
        Gate::authorize('update', $lesson->module->course);

        $this->lessonService->deleteLesson($lesson);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted successfully.'
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Lesson deleted successfully.');
    }

    /**
     * Reorder lessons
     */
    public function reorder(Request $request, CourseModule $module)
    {
        Gate::authorize('update', $module->course);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:course_lessons,id',
        ]);

        $this->lessonService->reorderLessons($module, $validated['order']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lessons reordered successfully.'
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Lessons reordered successfully.');
    }

    /**
     * Duplicate a lesson
     */
    public function duplicate(CourseLesson $lesson)
    {
        Gate::authorize('update', $lesson->module->course);

        $duplicateLesson = $this->lessonService->duplicateLesson($lesson);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson duplicated successfully.',
                'lesson' => $duplicateLesson
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Lesson duplicated successfully.');
    }

    /**
     * Move lesson to another module
     */
    public function move(Request $request, CourseLesson $lesson)
    {
        Gate::authorize('update', $lesson->module->course);

        $validated = $request->validate([
            'target_module_id' => 'required|exists:course_modules,id',
        ]);

        $targetModule = CourseModule::findOrFail($validated['target_module_id']);

        // Ensure target module belongs to the same course
        if ($targetModule->course_id !== $lesson->module->course_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot move lesson to a module in a different course.'
            ], 422);
        }

        $lesson = $this->lessonService->moveLesson($lesson, $targetModule);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson moved successfully.',
                'lesson' => $lesson
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Lesson moved successfully.');
    }

    /**
     * Upload content for a lesson
     */
    public function uploadContent(Request $request, CourseLesson $lesson)
    {
        Gate::authorize('update', $lesson->module->course);

        $validated = $request->validate([
            'content_type' => 'required|in:video,pdf,presentation,scorm',
            'file' => 'required|file',
        ]);

        try {
            $result = match($validated['content_type']) {
                'video' => $this->fileUploadService->uploadVideo($request->file('file'), $lesson->id),
                'pdf' => $this->fileUploadService->uploadPdf($request->file('file'), $lesson->id),
                'presentation' => $this->fileUploadService->uploadPresentation($request->file('file'), $lesson->id),
                'scorm' => $this->fileUploadService->uploadScorm($request->file('file'), $lesson->id),
            };

            // Update lesson with uploaded content
            $this->lessonService->updateLesson($lesson, [
                'content_path' => $result['path'],
                'content_type' => $validated['content_type'],
                'metadata' => $result['metadata'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content uploaded successfully.',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload content: ' . $e->getMessage()
            ], 500);
        }
    }
}
