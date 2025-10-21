<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptResponse;
use App\Models\AIGradingResult;
use App\Services\AttemptService;
use App\Services\AI\AIGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradingReviewController extends Controller
{
    public function __construct(
        protected AttemptService $attemptService,
        protected AIGradingService $aiGradingService
    ) {
        $this->middleware(['auth', 'can:grade,assessment'])->except(['index']);
    }

    /**
     * Display list of attempts requiring review
     */
    public function index(Request $request)
    {
        $query = AIGradingResult::where('flagged_for_review', true)
            ->whereNull('reviewed_at')
            ->with([
                'attemptResponse.attempt.user',
                'attemptResponse.attempt.assessment',
                'attemptResponse.question'
            ]);

        // Filter by assessment if provided
        if ($request->has('assessment_id')) {
            $query->whereHas('attemptResponse.attempt', function ($q) use ($request) {
                $q->where('assessment_id', $request->assessment_id);
            });
        }

        // Filter by confidence threshold
        if ($request->has('max_confidence')) {
            $query->where('confidence_score', '<=', $request->max_confidence);
        }

        $flaggedGradings = $query->orderBy('created_at', 'asc')->paginate(20);

        // Get assessments for filter dropdown
        $assessments = Assessment::whereHas('attempts.responses.aiGradingResult', function ($q) {
            $q->where('flagged_for_review', true)->whereNull('reviewed_at');
        })->get();

        return view('admin.grading.review-index', compact('flaggedGradings', 'assessments'));
    }

    /**
     * Display single response for review
     */
    public function show(AIGradingResult $gradingResult)
    {
        $gradingResult->load([
            'attemptResponse.attempt.user',
            'attemptResponse.attempt.assessment',
            'attemptResponse.question',
            'reviewedBy'
        ]);

        $response = $gradingResult->attemptResponse;
        $question = $response->question;
        $attempt = $response->attempt;

        // Get other responses from same assessment for plagiarism check
        $otherResponses = AttemptResponse::where('question_id', $question->id)
            ->where('id', '!=', $response->id)
            ->whereHas('attempt', function ($q) use ($attempt) {
                $q->where('assessment_id', $attempt->assessment_id)
                  ->where('status', '!=', 'in_progress');
            })
            ->with('attempt.user')
            ->get();

        return view('admin.grading.review-show', compact(
            'gradingResult',
            'response',
            'question',
            'attempt',
            'otherResponses'
        ));
    }

    /**
     * Update grading with instructor override
     */
    public function update(Request $request, AIGradingResult $gradingResult)
    {
        $request->validate([
            'human_score' => 'required|numeric|min:0|max:' . $gradingResult->attemptResponse->question->points,
            'human_feedback' => 'nullable|string|max:5000',
        ]);

        $response = $gradingResult->attemptResponse;
        $instructor = Auth::user();

        $this->attemptService->overrideAIGrading(
            $response,
            $instructor,
            $request->human_score,
            $request->human_feedback
        );

        return redirect()
            ->route('admin.grading.review.show', $gradingResult)
            ->with('success', 'Grading has been reviewed and updated successfully.');
    }

    /**
     * Accept AI grading without changes
     */
    public function accept(AIGradingResult $gradingResult)
    {
        $instructor = Auth::user();
        
        // Mark as reviewed with same score
        $gradingResult->review(
            $instructor,
            $gradingResult->ai_score,
            $gradingResult->ai_feedback
        );

        return redirect()
            ->route('admin.grading.review.index')
            ->with('success', 'AI grading has been accepted.');
    }

    /**
     * Batch review multiple gradings
     */
    public function batchReview(Request $request)
    {
        $request->validate([
            'grading_ids' => 'required|array',
            'grading_ids.*' => 'exists:ai_grading_results,id',
            'action' => 'required|in:accept,flag',
        ]);

        $instructor = Auth::user();
        $count = 0;

        foreach ($request->grading_ids as $gradingId) {
            $gradingResult = AIGradingResult::find($gradingId);
            
            if ($gradingResult && !$gradingResult->hasBeenReviewed()) {
                if ($request->action === 'accept') {
                    $gradingResult->review(
                        $instructor,
                        $gradingResult->ai_score,
                        $gradingResult->ai_feedback
                    );
                    $count++;
                }
            }
        }

        return redirect()
            ->route('admin.grading.review.index')
            ->with('success', "{$count} gradings have been reviewed.");
    }

    /**
     * Get statistics for grading review
     */
    public function statistics(Assessment $assessment = null)
    {
        $query = AIGradingResult::query();

        if ($assessment) {
            $query->whereHas('attemptResponse.attempt', function ($q) use ($assessment) {
                $q->where('assessment_id', $assessment->id);
            });
        }

        $stats = [
            'total_ai_graded' => $query->count(),
            'flagged_for_review' => $query->where('flagged_for_review', true)->count(),
            'pending_review' => $query->where('flagged_for_review', true)
                ->whereNull('reviewed_at')->count(),
            'reviewed' => $query->whereNotNull('reviewed_at')->count(),
            'avg_confidence' => round($query->avg('confidence_score'), 2),
            'low_confidence_count' => $query->where('confidence_score', '<', 0.7)->count(),
        ];

        if ($assessment) {
            return response()->json($stats);
        }

        return view('admin.grading.statistics', compact('stats'));
    }
}
