<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ContentAnalysis;
use App\Services\AI\AIContentAnalyzerService;
use App\Exceptions\AIServiceException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContentAnalyzerController extends Controller
{
    protected AIContentAnalyzerService $analyzerService;

    public function __construct(AIContentAnalyzerService $analyzerService)
    {
        $this->analyzerService = $analyzerService;
    }

    /**
     * Display analysis results for a course
     */
    public function show(Course $course)
    {
        $this->authorize('update', $course);

        $analysis = $this->analyzerService->getLatestAnalysis($course);

        return view('admin.content-analyzer.show', compact('course', 'analysis'));
    }

    /**
     * Trigger content analysis for a course
     */
    public function analyze(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        try {
            $analysis = $this->analyzerService->analyzeCourse($course);

            return redirect()
                ->route('admin.content-analyzer.show', $course)
                ->with('success', 'Content analysis completed successfully.');

        } catch (AIServiceException $e) {
            Log::error('Content analysis failed', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to analyze content: ' . $e->getMessage());
        }
    }

    /**
     * Re-analyze course content
     */
    public function reAnalyze(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        try {
            $analysis = $this->analyzerService->reAnalyzeCourse($course);

            return redirect()
                ->route('admin.content-analyzer.show', $course)
                ->with('success', 'Content re-analysis completed successfully.');

        } catch (AIServiceException $e) {
            Log::error('Content re-analysis failed', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to re-analyze content: ' . $e->getMessage());
        }
    }

    /**
     * Display analysis history for a course
     */
    public function history(Course $course)
    {
        $this->authorize('update', $course);

        $analyses = ContentAnalysis::where('course_id', $course->id)
            ->orderBy('analyzed_at', 'desc')
            ->paginate(10);

        return view('admin.content-analyzer.history', compact('course', 'analyses'));
    }
}
