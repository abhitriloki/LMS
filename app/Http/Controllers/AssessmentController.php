<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Services\AssessmentService;
use App\Services\AttemptService;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct(
        protected AssessmentService $assessmentService,
        protected AttemptService $attemptService
    ) {}

    /**
     * Display the specified assessment
     */
    public function show(Assessment $assessment)
    {
        // Check if user can access assessment
        if (!$this->assessmentService->canUserAccessAssessment($assessment, auth()->user())) {
            abort(403, 'You do not have access to this assessment');
        }

        $assessment->load(['course']);
        
        // Get user's attempts
        $attempts = $this->attemptService->getUserAttempts($assessment, auth()->user());
        $statistics = $this->attemptService->getUserAttemptStatistics($assessment, auth()->user());
        
        // Check if user can attempt
        $canAttempt = $this->assessmentService->canUserAttemptAssessment($assessment, auth()->user());

        return view('assessments.show', compact('assessment', 'attempts', 'statistics', 'canAttempt'));
    }

    /**
     * Display user's assessment history
     */
    public function myAttempts()
    {
        $attempts = auth()->user()
            ->assessmentAttempts()
            ->with(['assessment.course'])
            ->orderBy('started_at', 'desc')
            ->paginate(15);

        return view('assessments.my-attempts', compact('attempts'));
    }
}
