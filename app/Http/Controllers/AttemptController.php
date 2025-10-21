<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Question;
use App\Services\AttemptService;
use App\Services\AssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttemptController extends Controller
{
    public function __construct(
        protected AttemptService $attemptService,
        protected AssessmentService $assessmentService
    ) {}

    /**
     * Show assessment start page
     */
    public function start(Assessment $assessment)
    {
        // Check if user can access assessment
        if (!$this->assessmentService->canUserAccessAssessment($assessment, auth()->user())) {
            abort(403, 'You do not have access to this assessment');
        }

        // Check if user can attempt
        if (!$this->assessmentService->canUserAttemptAssessment($assessment, auth()->user())) {
            return redirect()
                ->route('assessments.show', $assessment)
                ->with('error', 'You have reached the maximum number of attempts for this assessment');
        }

        // Get user's previous attempts
        $previousAttempts = $this->attemptService->getUserAttempts($assessment, auth()->user());
        $statistics = $this->attemptService->getUserAttemptStatistics($assessment, auth()->user());

        return view('assessments.start', compact('assessment', 'previousAttempts', 'statistics'));
    }

    /**
     * Begin a new attempt
     */
    public function begin(Assessment $assessment)
    {
        // Check if user can attempt
        if (!$this->assessmentService->canUserAttemptAssessment($assessment, auth()->user())) {
            return back()->with('error', 'You cannot attempt this assessment');
        }

        try {
            $attempt = $this->attemptService->startAttempt($assessment, auth()->user());

            return redirect()->route('attempts.take', $attempt);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Take the assessment
     */
    public function take(AssessmentAttempt $attempt)
    {
        // Check if user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if attempt is still in progress
        if (!$attempt->isInProgress()) {
            return redirect()->route('attempts.results', $attempt);
        }

        // Check for time limit exceeded
        if ($attempt->isTimeLimitExceeded()) {
            $this->attemptService->autoSubmitAttempt($attempt);
            
            return redirect()
                ->route('attempts.results', $attempt)
                ->with('info', 'Time limit exceeded. Your attempt has been automatically submitted.');
        }

        // Get attempt with questions
        $attempt = $this->attemptService->getAttemptWithQuestions($attempt);
        $remainingTime = $this->attemptService->getRemainingTime($attempt);

        return view('assessments.take', compact('attempt', 'remainingTime'));
    }

    /**
     * Save a response
     */
    public function saveResponse(Request $request, AssessmentAttempt $attempt, Question $question)
    {
        // Check if user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'response' => 'required',
        ]);

        try {
            $response = $this->attemptService->saveResponse(
                $attempt,
                $question,
                $validated['response']
            );

            return response()->json([
                'success' => true,
                'message' => 'Response saved',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Submit the attempt
     */
    public function submit(AssessmentAttempt $attempt)
    {
        // Check if user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $this->attemptService->submitAttempt($attempt);

            return redirect()
                ->route('attempts.results', $attempt)
                ->with('success', 'Assessment submitted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show attempt results
     */
    public function results(AssessmentAttempt $attempt)
    {
        // Check if user can view results
        if ($attempt->user_id !== auth()->id() && !auth()->user()->isInstructor() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        // Check if results should be shown
        if ($attempt->user_id === auth()->id() && !$attempt->assessment->shouldShowResults()) {
            return redirect()
                ->route('assessments.show', $attempt->assessment)
                ->with('info', 'Results are not available for this assessment');
        }

        $results = $this->attemptService->getAttemptResults($attempt);

        return view('assessments.results', compact('attempt', 'results'));
    }

    /**
     * Review an attempt
     */
    public function review(AssessmentAttempt $attempt)
    {
        // Check if user can review
        if (!$this->attemptService->canReviewAttempt($attempt, auth()->user())) {
            abort(403, 'You cannot review this attempt');
        }

        $attempt->load(['assessment', 'responses.question.options']);
        $results = $this->attemptService->getAttemptResults($attempt);

        return view('assessments.review', compact('attempt', 'results'));
    }

    /**
     * Show grading interface for instructors
     */
    public function grade(AssessmentAttempt $attempt)
    {
        Gate::authorize('update', $attempt->assessment);

        $attempt->load(['user', 'assessment', 'responses.question.options']);

        return view('admin.assessments.grade', compact('attempt'));
    }

    /**
     * Save manual grading
     */
    public function saveGrade(Request $request, AssessmentAttempt $attempt)
    {
        Gate::authorize('update', $attempt->assessment);

        $validated = $request->validate([
            'responses' => 'required|array',
            'responses.*.response_id' => 'required|exists:attempt_responses,id',
            'responses.*.points_earned' => 'required|numeric|min:0',
            'responses.*.feedback' => 'nullable|string',
        ]);

        foreach ($validated['responses'] as $responseData) {
            $response = $attempt->responses()->find($responseData['response_id']);
            
            if ($response) {
                $this->attemptService->manuallyGradeResponse(
                    $response,
                    $responseData['points_earned'],
                    $responseData['feedback'] ?? null,
                    auth()->user()
                );
            }
        }

        return redirect()
            ->route('admin.assessments.attempts', $attempt->assessment)
            ->with('success', 'Grading saved successfully');
    }

    /**
     * Get remaining time for an attempt (AJAX)
     */
    public function remainingTime(AssessmentAttempt $attempt)
    {
        // Check if user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $remainingTime = $this->attemptService->getRemainingTime($attempt);

        return response()->json([
            'remaining_time' => $remainingTime,
            'time_exceeded' => $remainingTime !== null && $remainingTime <= 0,
        ]);
    }
}
