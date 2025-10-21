<?php

namespace App\Http\Controllers;

use App\Models\CourseLesson;
use App\Services\ContentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LessonViewController extends Controller
{
    protected ContentService $contentService;

    public function __construct(ContentService $contentService)
    {
        $this->contentService = $contentService;
        $this->middleware('auth');
    }

    /**
     * Display the lesson viewer
     */
    public function show(int $lessonId): View
    {
        $user = auth()->user();
        $data = $this->contentService->getLesson($lessonId, $user);

        $lesson = $data['lesson'];
        $course = $data['course'];
        $enrollment = $data['enrollment'];
        $progress = $data['progress'];

        // Get navigation
        $nextLesson = $this->contentService->getNextLesson($lesson);
        $previousLesson = $this->contentService->getPreviousLesson($lesson);

        // Get all modules with lessons for sidebar
        $modules = $course->modules()->with(['lessons' => function ($query) use ($enrollment) {
            $query->with(['progress' => function ($q) use ($enrollment) {
                $q->where('enrollment_id', $enrollment->id);
            }]);
        }])->get();

        return view('lessons.show', compact(
            'lesson',
            'course',
            'enrollment',
            'progress',
            'nextLesson',
            'previousLesson',
            'modules'
        ));
    }

    /**
     * Update lesson progress
     */
    public function updateProgress(Request $request, int $lessonId): JsonResponse
    {
        $validated = $request->validate([
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'position' => 'nullable|integer|min:0',
            'time_spent' => 'nullable|integer|min:0',
        ]);

        $user = auth()->user();
        $lesson = CourseLesson::findOrFail($lessonId);

        $progress = $this->contentService->trackProgress($user, $lesson, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Progress updated successfully',
            'data' => [
                'progress_percentage' => $progress->progress_percentage,
                'status' => $progress->status,
                'last_position' => $progress->last_position,
            ],
        ]);
    }

    /**
     * Save bookmark position
     */
    public function saveBookmark(Request $request, int $lessonId): JsonResponse
    {
        $validated = $request->validate([
            'position' => 'required|integer|min:0',
        ]);

        $user = auth()->user();
        $lesson = CourseLesson::findOrFail($lessonId);

        $progress = $this->contentService->bookmarkPosition(
            $user,
            $lesson,
            $validated['position']
        );

        return response()->json([
            'success' => true,
            'message' => 'Bookmark saved successfully',
            'data' => [
                'last_position' => $progress->last_position,
            ],
        ]);
    }

    /**
     * Restore bookmark position
     */
    public function getBookmark(int $lessonId): JsonResponse
    {
        $user = auth()->user();
        $lesson = CourseLesson::findOrFail($lessonId);

        $data = $this->contentService->getLesson($lessonId, $user);
        $progress = $data['progress'];

        return response()->json([
            'success' => true,
            'data' => [
                'last_position' => $progress->last_position ?? 0,
                'progress_percentage' => $progress->progress_percentage,
            ],
        ]);
    }

    /**
     * Mark lesson as complete
     */
    public function markComplete(int $lessonId): JsonResponse
    {
        $user = auth()->user();
        $lesson = CourseLesson::findOrFail($lessonId);

        $progress = $this->contentService->markComplete($user, $lesson);

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as complete',
            'data' => [
                'status' => $progress->status,
                'progress_percentage' => $progress->progress_percentage,
                'completed_at' => $progress->completed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get download URL for lesson content
     */
    public function download(int $lessonId): JsonResponse
    {
        $user = auth()->user();
        $lesson = CourseLesson::findOrFail($lessonId);

        $downloadUrl = $this->contentService->getDownloadUrl($lesson, $user);

        if (!$downloadUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Content is not downloadable',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $downloadUrl,
            ],
        ]);
    }
}
