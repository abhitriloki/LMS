<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Models\User;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now();

        $metrics = $this->analyticsService->getDashboardMetrics($startDate, $endDate);

        return view('admin.analytics.index', compact('metrics', 'startDate', 'endDate'));
    }

    /**
     * Get dashboard data (AJAX)
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now();

        $metrics = $this->analyticsService->getDashboardMetrics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get user analytics
     */
    public function getUserAnalytics(Request $request, User $user): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now();

        $analytics = $this->analyticsService->getUserAnalytics($user, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics(Request $request, Course $course): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now();

        $analytics = $this->analyticsService->getCourseAnalytics($course, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get department analytics
     */
    public function getDepartmentAnalytics(Request $request, Department $department): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now();

        $analytics = $this->analyticsService->getDepartmentAnalytics($department, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Track custom event
     */
    public function trackEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|string',
            'properties' => 'array',
        ]);

        $event = $this->analyticsService->trackEvent(
            $validated['event_type'],
            $request->user(),
            $validated['properties'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Event tracked successfully',
            'data' => $event,
        ]);
    }

    /**
     * Get enrollment trends
     */
    public function getEnrollmentTrends(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now();

        $courseId = $request->input('course_id');

        $query = \DB::table('course_enrollments')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $trends = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Get completion trends
     */
    public function getCompletionTrends(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now();

        $courseId = $request->input('course_id');

        $query = \DB::table('course_enrollments')
            ->where('status', 'completed')
            ->whereBetween('completion_date', [$startDate, $endDate]);

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $trends = $query->selectRaw('DATE(completion_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Get activity heatmap data
     */
    public function getActivityHeatmap(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now();

        $userId = $request->input('user_id');

        $query = \DB::table('analytics_events')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $heatmap = $query->selectRaw('DATE(created_at) as date, HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('date', 'hour')
            ->orderBy('date')
            ->orderBy('hour')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $heatmap,
        ]);
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $id = $request->input('id');

        $this->analyticsService->clearCache($type, $id);

        return response()->json([
            'success' => true,
            'message' => 'Analytics cache cleared successfully',
        ]);
    }
}
