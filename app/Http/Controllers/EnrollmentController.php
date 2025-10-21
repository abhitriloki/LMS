<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService,
        protected EnrollmentRepositoryInterface $enrollmentRepository
    ) {}

    /**
     * Display user's enrolled courses
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');

        $enrollments = match($status) {
            'active' => $this->enrollmentRepository->getActiveByUser($user),
            'completed' => $this->enrollmentRepository->getCompletedByUser($user),
            default => $this->enrollmentRepository->getByUser($user),
        };

        $statistics = $this->enrollmentService->getUserStatistics($user);
        $upcomingDeadlines = $this->enrollmentService->getUpcomingDeadlines($user);
        $overdueEnrollments = $this->enrollmentService->getOverdueEnrollments($user);

        return view('enrollments.index', compact(
            'enrollments',
            'statistics',
            'upcomingDeadlines',
            'overdueEnrollments',
            'status'
        ));
    }

    /**
     * Show enrollment confirmation page
     */
    public function show(Course $course)
    {
        $user = Auth::user();
        
        // Check if already enrolled
        $existingEnrollment = $this->enrollmentRepository->findByUserAndCourse($user, $course);
        
        if ($existingEnrollment) {
            return redirect()->route('enrollments.index')
                ->with('info', 'You are already enrolled in this course.');
        }

        // Check prerequisites
        $canEnroll = $this->enrollmentService->checkPrerequisites($user, $course);
        $missingPrerequisites = $this->enrollmentService->getMissingPrerequisites($user, $course);

        return view('enrollments.show', compact(
            'course',
            'canEnroll',
            'missingPrerequisites'
        ));
    }

    /**
     * Enroll user in a course
     */
    public function store(Request $request, Course $course)
    {
        $user = Auth::user();

        try {
            $enrollment = $this->enrollmentService->enroll($user, $course);

            return redirect()->route('enrollments.index')
                ->with('success', 'Successfully enrolled in ' . $course->title);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unenroll user from a course
     */
    public function destroy(Course $course)
    {
        $user = Auth::user();

        try {
            $this->enrollmentService->unenroll($user, $course);

            return redirect()->route('enrollments.index')
                ->with('success', 'Successfully unenrolled from ' . $course->title);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
