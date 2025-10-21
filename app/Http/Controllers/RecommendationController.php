<?php

namespace App\Http\Controllers;

use App\Models\AIRecommendation;
use App\Services\AI\AIRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    protected AIRecommendationService $recommendationService;

    public function __construct(AIRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Display user's recommendations
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $recommendations = AIRecommendation::where('user_id', $user->id)
            ->active()
            ->with('course.category')
            ->orderBy('score', 'desc')
            ->paginate(10);

        return view('recommendations.index', compact('recommendations'));
    }

    /**
     * Generate new recommendations for the user
     */
    public function generate(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();
            
            $recommendations = $this->recommendationService->generateRecommendations($user);

            return redirect()
                ->route('recommendations.index')
                ->with('success', 'New recommendations generated successfully! Found ' . count($recommendations) . ' courses for you.');

        } catch (\Exception $e) {
            Log::error('Failed to generate recommendations', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to generate recommendations. Please try again later.');
        }
    }

    /**
     * Accept a recommendation
     */
    public function accept(Request $request, AIRecommendation $recommendation): RedirectResponse
    {
        // Ensure user owns this recommendation
        if ($recommendation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'feedback' => 'nullable|string|max:500',
        ]);

        $this->recommendationService->recordFeedback(
            $recommendation,
            'accept',
            $validated['feedback'] ?? null
        );

        // Optionally enroll user in the course
        if ($request->has('enroll') && $request->input('enroll') === 'true') {
            return redirect()
                ->route('courses.enroll', $recommendation->course_id)
                ->with('success', 'Recommendation accepted! Proceeding to enrollment.');
        }

        return redirect()
            ->back()
            ->with('success', 'Recommendation accepted! You can enroll in this course anytime.');
    }

    /**
     * Reject a recommendation
     */
    public function reject(Request $request, AIRecommendation $recommendation): RedirectResponse
    {
        // Ensure user owns this recommendation
        if ($recommendation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'feedback' => 'nullable|string|max:500',
        ]);

        $this->recommendationService->recordFeedback(
            $recommendation,
            'reject',
            $validated['feedback'] ?? null
        );

        return redirect()
            ->back()
            ->with('success', 'Recommendation dismissed. We\'ll improve future recommendations based on your feedback.');
    }

    /**
     * Record general feedback on a recommendation
     */
    public function feedback(Request $request, AIRecommendation $recommendation): RedirectResponse
    {
        // Ensure user owns this recommendation
        if ($recommendation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'action' => 'required|in:accept,reject,helpful,not_helpful',
            'feedback' => 'nullable|string|max:500',
        ]);

        $action = in_array($validated['action'], ['accept', 'reject']) 
            ? $validated['action'] 
            : 'feedback';

        $this->recommendationService->recordFeedback(
            $recommendation,
            $action,
            $validated['feedback'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!'
        ]);
    }

    /**
     * Get recommendations for dashboard widget
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        
        $recommendations = AIRecommendation::where('user_id', $user->id)
            ->active()
            ->with('course.category')
            ->orderBy('score', 'desc')
            ->limit(3)
            ->get();

        // Check if user needs new recommendations
        $needsNew = $this->recommendationService->needsNewRecommendations($user);

        return view('recommendations.dashboard-widget', compact('recommendations', 'needsNew'));
    }
}
