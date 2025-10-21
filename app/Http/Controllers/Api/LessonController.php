<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Services\ContentService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        protected ContentService $contentService
    ) {}

    /**
     * Display the specified lesson.
     */
    public function show(Lesson $lesson)
    {
        $lesson->load('module.course');

        return response()->json([
            'success' => true,
            'data' => new LessonResource($lesson),
        ]);
    }

    /**
     * Track lesson progress.
     */
    public function trackProgress(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'time_spent' => 'required|integer|min:0',
            'bookmark_position' => 'nullable|integer|min:0',
            'completed' => 'boolean',
        ]);

        try {
            $this->contentService->trackProgress(
                $request->user(),
                $lesson,
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Progress tracked successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark lesson as complete.
     */
    public function markComplete(Request $request, Lesson $lesson)
    {
        try {
            $this->contentService->markComplete($request->user(), $lesson);

            return response()->json([
                'success' => true,
                'message' => 'Lesson marked as complete',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
