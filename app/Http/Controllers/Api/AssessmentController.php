<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    /**
     * Display assessments for a course.
     */
    public function index(Request $request, Course $course)
    {
        $perPage = $request->input('per_page', 15);

        $assessments = $course->assessments()
            ->where('is_published', true)
            ->withCount('questions')
            ->paginate($perPage);

        return AssessmentResource::collection($assessments);
    }

    /**
     * Display the specified assessment.
     */
    public function show(Assessment $assessment)
    {
        if (!$assessment->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found',
            ], 404);
        }

        $assessment->load(['course', 'questions.options']);

        return response()->json([
            'success' => true,
            'data' => new AssessmentResource($assessment),
        ]);
    }

    /**
     * Get user's attempts for an assessment.
     */
    public function attempts(Request $request, Assessment $assessment)
    {
        $attempts = $assessment->attempts()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attempts,
        ]);
    }
}
