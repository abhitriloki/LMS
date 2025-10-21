<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Models\AIRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get role-specific dashboard data
        $data = match($user->role) {
            'super_admin', 'hr_admin' => $this->getAdminDashboardData($user),
            'instructor' => $this->getInstructorDashboardData($user),
            default => $this->getEmployeeDashboardData($user),
        };
        
        // Get recent activity for all roles
        $data['recentActivity'] = $this->getRecentActivity($user);
        
        // Get upcoming deadlines
        $data['upcomingDeadlines'] = $this->getUpcomingDeadlines($user);
        
        return view('dashboard', $data);
    }
    
    /**
     * Get dashboard data for admin users
     */
    protected function getAdminDashboardData($user)
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalCourses' => Course::count(),
            'publishedCourses' => Course::where('is_published', true)->count(),
            'totalEnrollments' => Enrollment::count(),
            'activeEnrollments' => Enrollment::where('status', 'active')->count(),
            'completedEnrollments' => Enrollment::where('status', 'completed')->count(),
            'totalCertificates' => Certificate::count(),
            'averageCompletionRate' => $this->getAverageCompletionRate(),
        ];
        
        // Get enrollment trends (last 30 days)
        $enrollmentTrends = Enrollment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', Carbon::now()->subDays(30))
        ->groupBy('date')
        ->orderBy('date')
        ->get();
        
        // Get top courses by enrollment
        $topCourses = Course::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->limit(5)
            ->get();
        
        // Get department statistics
        $departmentStats = DB::table('departments')
            ->leftJoin('users', 'departments.id', '=', 'users.department_id')
            ->leftJoin('course_enrollments', 'users.id', '=', 'course_enrollments.user_id')
            ->select(
                'departments.name',
                DB::raw('COUNT(DISTINCT users.id) as user_count'),
                DB::raw('COUNT(DISTINCT course_enrollments.id) as enrollment_count'),
                DB::raw('AVG(course_enrollments.progress_percentage) as avg_progress')
            )
            ->groupBy('departments.id', 'departments.name')
            ->get();
        
        return [
            'stats' => $stats,
            'enrollmentTrends' => $enrollmentTrends,
            'topCourses' => $topCourses,
            'departmentStats' => $departmentStats,
        ];
    }
    
    /**
     * Get dashboard data for instructor users
     */
    protected function getInstructorDashboardData($user)
    {
        $stats = [
            'totalCourses' => Course::where('created_by', $user->id)->count(),
            'publishedCourses' => Course::where('created_by', $user->id)
                ->where('is_published', true)
                ->count(),
            'totalStudents' => Enrollment::whereHas('course', function($query) use ($user) {
                $query->where('created_by', $user->id);
            })->distinct('user_id')->count('user_id'),
            'pendingGrading' => Assessment::whereHas('course', function($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->whereHas('attempts', function($query) {
                $query->whereHas('responses', function($q) {
                    $q->whereNull('score')
                      ->whereHas('question', function($qq) {
                          $qq->where('type', 'essay');
                      });
                });
            })
            ->count(),
        ];
        
        // Get instructor's courses with enrollment stats
        $courses = Course::where('created_by', $user->id)
            ->withCount(['enrollments', 'assessments'])
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Get recent student activity
        $recentStudentActivity = Enrollment::whereHas('course', function($query) use ($user) {
            $query->where('created_by', $user->id);
        })
        ->with(['user', 'course'])
        ->orderBy('last_accessed_at', 'desc')
        ->limit(10)
        ->get();
        
        return [
            'stats' => $stats,
            'courses' => $courses,
            'recentStudentActivity' => $recentStudentActivity,
        ];
    }
    
    /**
     * Get dashboard data for employee users
     */
    protected function getEmployeeDashboardData($user)
    {
        $enrollments = Enrollment::where('user_id', $user->id)
            ->with('course.category')
            ->get();
        
        $stats = [
            'enrolledCourses' => $enrollments->count(),
            'activeCourses' => $enrollments->where('status', 'active')->count(),
            'completedCourses' => $enrollments->where('status', 'completed')->count(),
            'certificates' => Certificate::where('user_id', $user->id)->count(),
            'learningHours' => $this->calculateLearningHours($user),
            'averageProgress' => $enrollments->avg('progress_percentage') ?? 0,
        ];
        
        // Get in-progress courses
        $inProgressCourses = $enrollments->where('status', 'active')
            ->sortByDesc('last_accessed_at')
            ->take(4);
        
        // Get AI recommendations
        $recommendations = AIRecommendation::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('course.category')
            ->orderBy('score', 'desc')
            ->limit(3)
            ->get();
        
        // Get recent certificates
        $recentCertificates = Certificate::where('user_id', $user->id)
            ->with('course')
            ->orderBy('issued_at', 'desc')
            ->limit(3)
            ->get();
        
        return [
            'stats' => $stats,
            'inProgressCourses' => $inProgressCourses,
            'recommendations' => $recommendations,
            'recentCertificates' => $recentCertificates,
        ];
    }
    
    /**
     * Get recent activity for the user
     */
    protected function getRecentActivity($user)
    {
        if (in_array($user->role, ['super_admin', 'hr_admin'])) {
            // System-wide activity for admins
            return AnalyticsEvent::with(['user', 'course'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } else {
            // User-specific activity
            return AnalyticsEvent::where('user_id', $user->id)
                ->with('course')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }
    }
    
    /**
     * Get upcoming deadlines for the user
     */
    protected function getUpcomingDeadlines($user)
    {
        return Enrollment::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('deadline')
            ->where('deadline', '>', Carbon::now())
            ->with('course')
            ->orderBy('deadline')
            ->limit(5)
            ->get();
    }
    
    /**
     * Calculate total learning hours for a user
     */
    protected function calculateLearningHours($user)
    {
        $totalMinutes = AnalyticsEvent::where('user_id', $user->id)
            ->where('event_type', 'lesson_viewed')
            ->sum(DB::raw("CAST(JSON_EXTRACT(properties, '$.duration') AS UNSIGNED)"));
        
        return round($totalMinutes / 60, 1);
    }
    
    /**
     * Get average completion rate across all courses
     */
    protected function getAverageCompletionRate()
    {
        $total = Enrollment::count();
        $completed = Enrollment::where('status', 'completed')->count();
        
        return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    }
}
