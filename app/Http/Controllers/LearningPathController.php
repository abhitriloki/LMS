<?php

namespace App\Http\Controllers;

use App\Models\AILearningPath;
use App\Services\AI\AILearningPathService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LearningPathController extends Controller
{
    protected AILearningPathService $learningPathService;

    public function __construct(AILearningPathService $learningPathService)
    {
        $this->learningPathService = $learningPathService;
    }

    /**
     * Display a listing of the user's learning paths
     */
    public function index()
    {
        $user = Auth::user();
        
        $learningPaths = AILearningPath::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('learning-paths.index', compact('learningPaths'));
    }

    /**
     * Show the form for creating a new learning path
     */
    public function create()
    {
        return view('learning-paths.create');
    }

    /**
     * Store a newly created learning path
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_role' => 'required|string|max:255',
        ]);

        try {
            $user = Auth::user();
            
            $learningPath = $this->learningPathService->generateLearningPath(
                $user,
                $validated['target_role']
            );

            return redirect()
                ->route('learning-paths.show', $learningPath)
                ->with('success', 'Learning path generated successfully! Review and start when ready.');
                
        } catch (\Exception $e) {
            Log::error('Failed to generate learning path', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Failed to generate learning path. Please try again.');
        }
    }

    /**
     * Display the specified learning path
     */
    public function show(AILearningPath $learningPath)
    {
        $this->authorize('view', $learningPath);
        
        $learningPath->load('user');
        
        // Get next course
        $nextCourse = $this->learningPathService->getNextCourse($learningPath);
        
        // Get course details for the path
        $pathCourses = collect($learningPath->path_data['courses'] ?? []);
        $courseIds = $pathCourses->pluck('course_id')->toArray();
        
        $courses = \App\Models\Course::whereIn('id', $courseIds)
            ->with('category')
            ->get()
            ->keyBy('id');
        
        // Enrich path courses with full course data
        $enrichedCourses = $pathCourses->map(function ($pathCourse) use ($courses) {
            $course = $courses->get($pathCourse['course_id']);
            return array_merge($pathCourse, [
                'course' => $course,
            ]);
        });
        
        // Get user's enrollments for these courses
        $enrollments = \App\Models\Enrollment::where('user_id', $learningPath->user_id)
            ->whereIn('course_id', $courseIds)
            ->get()
            ->keyBy('course_id');
        
        return view('learning-paths.show', compact(
            'learningPath',
            'enrichedCourses',
            'enrollments',
            'nextCourse'
        ));
    }

    /**
     * Start a learning path
     */
    public function start(AILearningPath $learningPath)
    {
        $this->authorize('update', $learningPath);
        
        try {
            $learningPath->start();
            
            return redirect()
                ->route('learning-paths.show', $learningPath)
                ->with('success', 'Learning path started! Begin with your first course.');
                
        } catch (\Exception $e) {
            Log::error('Failed to start learning path', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to start learning path.');
        }
    }

    /**
     * Pause a learning path
     */
    public function pause(AILearningPath $learningPath)
    {
        $this->authorize('update', $learningPath);
        
        try {
            $learningPath->pause();
            
            return redirect()
                ->route('learning-paths.show', $learningPath)
                ->with('success', 'Learning path paused. You can resume anytime.');
                
        } catch (\Exception $e) {
            Log::error('Failed to pause learning path', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to pause learning path.');
        }
    }

    /**
     * Resume a learning path
     */
    public function resume(AILearningPath $learningPath)
    {
        $this->authorize('update', $learningPath);
        
        try {
            $learningPath->resume();
            
            return redirect()
                ->route('learning-paths.show', $learningPath)
                ->with('success', 'Learning path resumed! Continue your learning journey.');
                
        } catch (\Exception $e) {
            Log::error('Failed to resume learning path', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to resume learning path.');
        }
    }

    /**
     * Optimize an existing learning path
     */
    public function optimize(AILearningPath $learningPath)
    {
        $this->authorize('update', $learningPath);
        
        try {
            $optimizedPath = $this->learningPathService->optimizePath($learningPath);
            
            return redirect()
                ->route('learning-paths.show', $optimizedPath)
                ->with('success', 'Learning path optimized based on your current progress!');
                
        } catch (\Exception $e) {
            Log::error('Failed to optimize learning path', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to optimize learning path. Please try again.');
        }
    }

    /**
     * Adjust learning path based on performance
     */
    public function adjust(Request $request, AILearningPath $learningPath)
    {
        $this->authorize('update', $learningPath);
        
        $validated = $request->validate([
            'average_score' => 'nullable|numeric|min:0|max:100',
            'completion_rate' => 'nullable|numeric|min:0|max:100',
            'time_spent' => 'nullable|integer|min:0',
            'estimated_time' => 'nullable|integer|min:0',
        ]);

        try {
            // Get performance data
            $performanceData = [
                'average_score' => $validated['average_score'] ?? $this->calculateAverageScore($learningPath),
                'completion_rate' => $validated['completion_rate'] ?? $learningPath->progress_percentage,
                'time_spent' => $validated['time_spent'] ?? 0,
                'estimated_time' => $validated['estimated_time'] ?? $learningPath->estimated_duration,
            ];
            
            $adjustedPath = $this->learningPathService->adjustPath($learningPath, $performanceData);
            
            return redirect()
                ->route('learning-paths.show', $adjustedPath)
                ->with('success', 'Learning path adjusted based on your performance!');
                
        } catch (\Exception $e) {
            Log::error('Failed to adjust learning path', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to adjust learning path. Please try again.');
        }
    }

    /**
     * Update progress for a learning path
     */
    public function updateProgress(AILearningPath $learningPath)
    {
        $this->authorize('view', $learningPath);
        
        try {
            $this->learningPathService->updateProgress($learningPath);
            
            return response()->json([
                'success' => true,
                'progress' => $learningPath->fresh()->progress_percentage,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update learning path progress', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress',
            ], 500);
        }
    }

    /**
     * Delete a learning path
     */
    public function destroy(AILearningPath $learningPath)
    {
        $this->authorize('delete', $learningPath);
        
        try {
            $learningPath->delete();
            
            return redirect()
                ->route('learning-paths.index')
                ->with('success', 'Learning path deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Failed to delete learning path', [
                'path_id' => $learningPath->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to delete learning path.');
        }
    }

    /**
     * Calculate average score for courses in the learning path
     */
    protected function calculateAverageScore(AILearningPath $learningPath): float
    {
        $pathCourses = collect($learningPath->path_data['courses'] ?? []);
        $courseIds = $pathCourses->pluck('course_id')->toArray();
        
        $enrollments = \App\Models\Enrollment::where('user_id', $learningPath->user_id)
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('final_score')
            ->get();
        
        if ($enrollments->isEmpty()) {
            return 0;
        }
        
        return $enrollments->avg('final_score');
    }
}
