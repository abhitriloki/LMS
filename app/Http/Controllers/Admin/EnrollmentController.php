<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService,
        protected EnrollmentRepositoryInterface $enrollmentRepository
    ) {
        $this->middleware(['auth', 'role:super_admin,hr_admin,instructor']);
    }

    /**
     * Display all enrollments with filters
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'course_id',
            'user_id',
            'department_id',
            'status',
            'enrollment_type',
            'per_page'
        ]);

        $enrollments = $this->enrollmentRepository->search($filters);
        $courses = Course::published()->orderBy('title')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.enrollments.index', compact('enrollments', 'courses', 'departments', 'filters'));
    }

    /**
     * Show bulk enrollment form
     */
    public function create(Request $request)
    {
        $courseId = $request->get('course_id');
        $course = $courseId ? Course::findOrFail($courseId) : null;
        
        $courses = Course::published()->orderBy('title')->get();
        $departments = Department::orderBy('name')->get();
        $users = User::where('role', '!=', 'super_admin')->orderBy('name')->get();

        return view('admin.enrollments.create', compact('courses', 'departments', 'users', 'course'));
    }

    /**
     * Store bulk enrollments
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'enrollment_type' => 'required|in:assigned,mandatory',
            'target_type' => 'required|in:users,department',
            'user_ids' => 'required_if:target_type,users|array',
            'user_ids.*' => 'exists:users,id',
            'department_id' => 'required_if:target_type,department|exists:departments,id',
            'deadline' => 'nullable|date|after:today',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $deadline = isset($validated['deadline']) ? Carbon::parse($validated['deadline']) : null;
        $enrolledBy = Auth::user();

        try {
            if ($validated['target_type'] === 'department') {
                $department = Department::findOrFail($validated['department_id']);
                $results = $this->enrollmentService->enrollDepartment(
                    $department,
                    $course,
                    $validated['enrollment_type'],
                    $enrolledBy,
                    $deadline
                );
            } else {
                $users = User::whereIn('id', $validated['user_ids'])->get();
                $results = $this->enrollmentService->bulkEnroll(
                    $users,
                    $course,
                    $validated['enrollment_type'],
                    $enrolledBy,
                    $deadline
                );
            }

            $successCount = count($results['success']);
            $failedCount = count($results['failed']);

            $message = "Successfully enrolled {$successCount} user(s).";
            if ($failedCount > 0) {
                $message .= " {$failedCount} enrollment(s) failed.";
            }

            return redirect()->route('admin.enrollments.index')
                ->with('success', $message)
                ->with('enrollment_results', $results);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Show enrollment details
     */
    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['course', 'user', 'enrolledBy', 'lessonProgress.lesson']);
        
        return view('admin.enrollments.show', compact('enrollment'));
    }

    /**
     * Show edit enrollment form
     */
    public function edit(Enrollment $enrollment)
    {
        return view('admin.enrollments.edit', compact('enrollment'));
    }

    /**
     * Update enrollment
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,completed,suspended,expired',
            'deadline' => 'nullable|date',
        ]);

        try {
            if (isset($validated['status'])) {
                $this->enrollmentService->updateStatus($enrollment, $validated['status']);
            }

            if (isset($validated['deadline'])) {
                $deadline = $validated['deadline'] ? Carbon::parse($validated['deadline']) : null;
                $this->enrollmentService->updateDeadline($enrollment, $deadline);
            }

            return redirect()->route('admin.enrollments.show', $enrollment)
                ->with('success', 'Enrollment updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Delete enrollment
     */
    public function destroy(Enrollment $enrollment)
    {
        try {
            $this->enrollmentRepository->delete($enrollment);

            return redirect()->route('admin.enrollments.index')
                ->with('success', 'Enrollment deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show course enrollment statistics
     */
    public function courseStats(Course $course)
    {
        $statistics = $this->enrollmentService->getCourseStatistics($course);
        $enrollments = $this->enrollmentRepository->getByCourse($course);

        return view('admin.enrollments.course-stats', compact('course', 'statistics', 'enrollments'));
    }
}
