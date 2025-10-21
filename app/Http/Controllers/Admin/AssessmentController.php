<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssessmentController extends Controller
{
    public function __construct(
        protected AssessmentService $assessmentService
    ) {}

    /**
     * Display a listing of assessments
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'course_id' => $request->input('course_id'),
            'is_published' => $request->input('is_published'),
            'per_page' => $request->input('per_page', 15),
        ];

        // If user is instructor, only show their assessments
        if (auth()->user()->isInstructor() && !auth()->user()->isAdmin()) {
            $filters['created_by'] = auth()->id();
        }

        $assessments = $this->assessmentService->searchAssessments($filters);
        $courses = Course::where('is_published', true)->get();

        return view('admin.assessments.index', compact('assessments', 'courses'));
    }

    /**
     * Show the form for creating a new assessment
     */
    public function create(Request $request)
    {
        $courseId = $request->input('course_id');
        $course = $courseId ? Course::findOrFail($courseId) : null;
        
        // Get courses user can create assessments for
        $courses = Course::where('is_published', true)->get();

        return view('admin.assessments.create', compact('courses', 'course'));
    }

    /**
     * Store a newly created assessment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'passing_score' => 'required|numeric|min:0|max:100',
            'time_limit' => 'nullable|integer|min:0',
            'max_attempts' => 'nullable|integer|min:0',
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
            'show_results' => 'boolean',
            'show_correct_answers' => 'boolean',
            'allow_review' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        Gate::authorize('update', $course);

        $assessment = $this->assessmentService->createAssessment($validated, auth()->user());

        return redirect()
            ->route('admin.assessments.show', $assessment)
            ->with('success', 'Assessment created successfully');
    }

    /**
     * Display the specified assessment
     */
    public function show(Assessment $assessment)
    {
        Gate::authorize('view', $assessment);

        $assessment->load(['course', 'creator', 'questions.options']);
        $statistics = $this->assessmentService->getAssessmentStatistics($assessment);

        return view('admin.assessments.show', compact('assessment', 'statistics'));
    }

    /**
     * Show the form for editing the specified assessment
     */
    public function edit(Assessment $assessment)
    {
        Gate::authorize('update', $assessment);

        $assessment->load(['course', 'questions.options']);
        $courses = Course::where('is_published', true)->get();

        return view('admin.assessments.edit', compact('assessment', 'courses'));
    }

    /**
     * Update the specified assessment
     */
    public function update(Request $request, Assessment $assessment)
    {
        Gate::authorize('update', $assessment);

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'passing_score' => 'required|numeric|min:0|max:100',
            'time_limit' => 'nullable|integer|min:0',
            'max_attempts' => 'nullable|integer|min:0',
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
            'show_results' => 'boolean',
            'show_correct_answers' => 'boolean',
            'allow_review' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $this->assessmentService->updateAssessment($assessment, $validated);

        return redirect()
            ->route('admin.assessments.show', $assessment)
            ->with('success', 'Assessment updated successfully');
    }

    /**
     * Remove the specified assessment
     */
    public function destroy(Assessment $assessment)
    {
        Gate::authorize('delete', $assessment);

        try {
            $this->assessmentService->deleteAssessment($assessment);
            
            return redirect()
                ->route('admin.assessments.index')
                ->with('success', 'Assessment deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Publish an assessment
     */
    public function publish(Assessment $assessment)
    {
        Gate::authorize('update', $assessment);

        try {
            $this->assessmentService->publishAssessment($assessment);
            
            return back()->with('success', 'Assessment published successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unpublish an assessment
     */
    public function unpublish(Assessment $assessment)
    {
        Gate::authorize('update', $assessment);

        $this->assessmentService->unpublishAssessment($assessment);

        return back()->with('success', 'Assessment unpublished successfully');
    }

    /**
     * Clone an assessment
     */
    public function clone(Assessment $assessment)
    {
        Gate::authorize('view', $assessment);

        $newAssessment = $this->assessmentService->cloneAssessment($assessment, auth()->user());

        return redirect()
            ->route('admin.assessments.edit', $newAssessment)
            ->with('success', 'Assessment cloned successfully');
    }

    /**
     * Show attempts for an assessment
     */
    public function attempts(Assessment $assessment)
    {
        Gate::authorize('view', $assessment);

        $attempts = $assessment->attempts()
            ->with(['user'])
            ->orderBy('started_at', 'desc')
            ->paginate(20);

        return view('admin.assessments.attempts', compact('assessment', 'attempts'));
    }
}
