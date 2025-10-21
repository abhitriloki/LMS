<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService
    ) {}

    /**
     * Display a listing of user's enrollments.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = $request->user()
            ->enrollments()
            ->with(['course.category', 'certificate']);

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy($sortBy, $sortOrder);

        $enrollments = $query->paginate($perPage);

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Enroll in a course.
     */
    public function store(Request $request, Course $course)
    {
        try {
            $enrollment = $this->enrollmentService->enroll(
                $request->user(),
                $course,
                'self'
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully enrolled in course',
                'data' => new EnrollmentResource($enrollment->load(['course', 'user'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified enrollment.
     */
    public function show(Enrollment $enrollment)
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['course.category', 'user', 'certificate']);

        return response()->json([
            'success' => true,
            'data' => new EnrollmentResource($enrollment),
        ]);
    }

    /**
     * Unenroll from a course.
     */
    public function destroy(Enrollment $enrollment)
    {
        $this->authorize('delete', $enrollment);

        try {
            $this->enrollmentService->unenroll($enrollment);

            return response()->json([
                'success' => true,
                'message' => 'Successfully unenrolled from course',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
