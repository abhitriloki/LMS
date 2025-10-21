<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\AssessmentAttempt;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportService
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Generate a report
     */
    public function generateReport(string $type, array $parameters, User $user): Report
    {
        $report = Report::create([
            'name' => $parameters['name'] ?? $this->getDefaultReportName($type),
            'description' => $parameters['description'] ?? null,
            'report_type' => $type,
            'parameters' => $parameters,
            'generated_by' => $user->id,
            'status' => 'pending',
            'file_format' => $parameters['format'] ?? 'pdf',
        ]);

        // Generate the report data
        $data = $this->generateReportData($type, $parameters);

        // Export to file
        $filePath = $this->exportReport($report, $data);

        // Mark as completed
        $report->markAsCompleted($filePath);

        return $report->fresh();
    }

    /**
     * Schedule a report
     */
    public function scheduleReport(string $type, array $parameters, string $schedule, User $user): Report
    {
        $scheduleConfig = $this->parseSchedule($schedule);

        $report = Report::create([
            'name' => $parameters['name'] ?? $this->getDefaultReportName($type),
            'description' => $parameters['description'] ?? null,
            'report_type' => $type,
            'parameters' => $parameters,
            'generated_by' => $user->id,
            'status' => 'pending',
            'file_format' => $parameters['format'] ?? 'pdf',
            'scheduled' => true,
            'schedule_config' => $scheduleConfig,
            'next_run_at' => $this->calculateNextRunTime($scheduleConfig),
        ]);

        return $report;
    }

    /**
     * Generate report data based on type
     */
    protected function generateReportData(string $type, array $parameters): array
    {
        $startDate = isset($parameters['start_date']) 
            ? Carbon::parse($parameters['start_date']) 
            : now()->subDays(30);
        
        $endDate = isset($parameters['end_date']) 
            ? Carbon::parse($parameters['end_date']) 
            : now();

        return match ($type) {
            'user_activity' => $this->generateUserActivityReport($parameters, $startDate, $endDate),
            'course_completion' => $this->generateCourseCompletionReport($parameters, $startDate, $endDate),
            'department_performance' => $this->generateDepartmentPerformanceReport($parameters, $startDate, $endDate),
            'compliance' => $this->generateComplianceReport($parameters, $startDate, $endDate),
            'assessment_results' => $this->generateAssessmentResultsReport($parameters, $startDate, $endDate),
            'enrollment_summary' => $this->generateEnrollmentSummaryReport($parameters, $startDate, $endDate),
            'certificate_issuance' => $this->generateCertificateIssuanceReport($parameters, $startDate, $endDate),
            'learning_hours' => $this->generateLearningHoursReport($parameters, $startDate, $endDate),
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };
    }

    /**
     * Generate user activity report
     */
    protected function generateUserActivityReport(array $parameters, Carbon $startDate, Carbon $endDate): array
    {
        $query = User::query();

        if (isset($parameters['department_id'])) {
            $query->where('department_id', $parameters['department_id']);
        }

        if (isset($parameters['role'])) {
            $query->where('role', $parameters['role']);
        }

        $users = $query->get();

        $data = $users->map(function ($user) use ($startDate, $endDate) {
            $analytics = $this->analyticsService->getUserAnalytics($user, $startDate, $endDate);
            
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department?->name,
                'enrollments' => $analytics['enrollments']['total'],
                'completed_courses' => $analytics['enrollments']['completed'],
                'in_progress' => $analytics['enrollments']['in_progress'],
                'average_progress' => round($analytics['progress']['average_progress'], 2),
                'assessment_attempts' => $analytics['assessments']['total_attempts'],
                'average_score' => round($analytics['assessments']['average_score'], 2),
                'learning_hours' => round($analytics['learning_time'], 2),
                'last_activity' => $user->last_login_at?->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'title' => 'User Activity Report',
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'data' => $data->toArray(),
            'summary' => [
                'total_users' => $users->count(),
                'active_users' => $users->where('last_login_at', '>=', $startDate)->count(),
                'total_enrollments' => $data->sum('enrollments'),
                'total_completions' => $data->sum('completed_courses'),
                'average_progress' => round($data->avg('average_progress'), 2),
                'total_learning_hours' => round($data->sum('learning_hours'), 2),
            ],
        ];
    }

    /**
     * Generate course completion report
     */
    protected function generateCourseCompletionReport(array $parameters, Carbon $startDate, Carbon $endDate): array
    {
        $query = Course::where('is_published', true);

        if (isset($parameters['category_id'])) {
            $query->where('category_id', $parameters['category_id']);
        }

        if (isset($parameters['course_id'])) {
            $query->where('id', $parameters['course_id']);
        }

        $courses = $query->get();

        $data = $courses->map(function ($course) use ($startDate, $endDate) {
            $analytics = $this->analyticsService->getCourseAnalytics($course, $startDate, $endDate);
            
            return [
                'course_id' => $course->id,
                'title' => $course->title,
                'category' => $course->category?->name,
                'total_enrollments' => $analytics['enrollments']['total'],
                'new_enrollments' => $analytics['enrollments']['new'],
                'completed' => $analytics['completion']['completed'],
                'completion_rate' => round($analytics['completion']['completion_rate'], 2),
                'average_completion_time' => round($analytics['completion']['average_completion_time'] ?? 0, 2),
                'average_score' => round($analytics['assessments']['average_score'], 2),
                'pass_rate' => round($analytics['assessments']['pass_rate'], 2),
            ];
        });

        return [
            'title' => 'Course Completion Report',
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'data' => $data->toArray(),
            'summary' => [
                'total_courses' => $courses->count(),
                'total_enrollments' => $data->sum('total_enrollments'),
                'total_completions' => $data->sum('completed'),
                'average_completion_rate' => round($data->avg('completion_rate'), 2),
                'average_score' => round($data->avg('average_score'), 2),
            ],
        ];
    }

    /**
     * Generate department performance report
     */
    protected function generateDepartmentPerformanceReport(array $parameters, Carbon $startDate, Carbon $endDate): array
    {
        $query = Department::query();

        if (isset($parameters['department_id'])) {
            $query->where('id', $parameters['department_id']);
        }

        $departments = $query->get();

        $data = $departments->map(function ($department) use ($startDate, $endDate) {
            $analytics = $this->analyticsService->getDepartmentAnalytics($department, $startDate, $endDate);
            
            return [
                'department_id' => $department->id,
                'name' => $department->name,
                'total_users' => $analytics['users']['total'],
                'active_users' => $analytics['users']['active'],
                'enrollments' => $analytics['enrollments']['total'],
                'completed' => $analytics['enrollments']['completed'],
                'completion_rate' => round($analytics['completion']['completion_rate'], 2),
                'compliance_rate' => round($analytics['compliance']['compliance_rate'], 2),
                'overdue' => $analytics['compliance']['overdue'],
            ];
        });

        return [
            'title' => 'Department Performance Report',
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'data' => $data->toArray(),
            'summary' => [
                'total_departments' => $departments->count(),
                'total_users' => $data->sum('total_users'),
                'total_enrollments' => $data->sum('enrollments'),
                'total_completions' => $data->sum('completed'),
                'average_completion_rate' => round($data->avg('completion_rate'), 2),
                'average_compliance_rate' => round($data->avg('compliance_rate'), 2),
            ],
        ];
    }

    /**
     * Generate compliance report
     */
    protected function generateComplianceReport(array $parameters, Carbon $startDate, Carbon $endDate): array
    {
        $mandatoryCourses = Course::where('is_mandatory', true)->get();

        $data = $mandatoryCourses->map(function ($course) use ($startDate, $endDate) {
            $totalUsers = User::count();
            $completedUsers = Enrollment::where('course_id', $course->id)
                ->where('status', 'completed')
                ->distinct('user_id')
                ->count('user_id');
            
            $overdueUsers = Enrollment::where('course_id', $course->id)
                ->where('status', 'active')
                ->whereNotNull('deadline')
                ->where('deadline', '<', now())
                ->count();

            return [
                'course_id' => $course->id,
                'title' => $course->title,
                'required_users' => $totalUsers,
                'completed_users' => $completedUsers,
                'compliance_rate' => round(($completedUsers / $totalUsers) * 100, 2),
                'overdue_users' => $overdueUsers,
                'deadline' => $course->deadline ?? 'N/A',
            ];
        });

        return [
            'title' => 'Compliance Report',
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'data' => $data->toArray(),
            'summary' => [
                'total_mandatory_courses' => $mandatoryCourses->count(),
                'average_compliance_rate' => round($data->avg('compliance_rate'), 2),
                'total_overdue' => $data->sum('overdue_users'),
            ],
        ];
    }

    /**
     * Generate assessment results report
     */
    protected function generateAssessmentResultsReport(array $parameters, Carbon $startDate, Carbon $endDate): array
    {
        $query = AssessmentAttempt::whereBetween('created_at', [$startDate, $endDate]);

        if (isset($parameters['course_id'])) {
            $assessmentIds = Course::find($parameters['course_id'])->assessments()->pluck('id');
            $query->whereIn('assessment_id', $assessmentIds);
        }

        if (isset($parameters['assessment_id'])) {
            $query->where('assessment_id', $parameters['assessment_id']);
        }

        $attempts = $query->with(['assessment', 'user'])->get();

        $data = $attempts->map(function ($attempt) {
            return [
                'attempt_id' => $attempt->id,
                'user_name' => $attempt->user->name,
                'assessment_title' => $attempt->assessment->title,
                'score' => $attempt->score,
                'passed' => $attempt->passed ? 'Yes' : 'No',
                'attempt_number' => $attempt->attempt_number,
                'time_taken' => $attempt->time_taken,
                'submitted_at' => $attempt->submitted_at?->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'title' => 'Assessment Results Report',
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'data' => $data->toArray(),
            'summary' => [
                'total_attempts' => $attempts->count(),
                'passed' => $attempts->where('passed', true)->count(),
                'failed' => $attempts->where('passed', false)->count(),
                'average_score' => round($attempts->avg('score'), 2),
                'pass_rate' => $attempts->count() > 0 
                    ? round(($attempts->where('passed', true)->count() / $attempts->count()) * 100, 2) 
                    : 0,
            ],
        ];
    }

    /**
     * Generate enrollment summary report
     */
    protected function generateEnrollmentSummaryReport(array $parameters, Carbon $startDate, Carbon $endDate): array
    {
        $query = Enrollment::whereBetween('created_at', [$startDate, $endDate]);

        if (isset($parameters['course_id'])) {
            $query->where('course_id', $parameters['course_id']);
        }

        if (isset($parameters['department_id'])) {
            $userIds = Department::find($parameters['department_id'])->users->pluck('id');
            $query->whereIn('user_id', $userIds);
        }

        $enrollments = $query->with(['course', 'user'])->get();

        $data = $enrollments->map(function ($enrollment) {
            return [
                'enrollment_id' => $enrollment->id,
                'user_name' => $enrollment->user->name,
                'course_title' => $enrollment->course->title,
                'enrollment_type' => $enrollment->enrollment_type,
                'status' => $enrollment->status,
                'progress' => round($enrollment->progress_percentage, 2),
                'enrolled_at' => $enrollment->created_at->format('Y-m-d H:i:s'),
                'deadline' => $enrollment->deadline?->format('Y-m-d'),
                'completed_at' => $enrollment->completion_date?->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'title' => 'Enrollment Summary Report',
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'data' => $data->toArray(),
            'summary' => [
                'total_enrollments' => $enrollments->count(),
                'active' => $enrollments->where('status', 'active')->count(),
                'completed' => $enrollments->where('status', 'completed')->count(),
                'average_progress' => round($enrollments->avg('progress_percentage'), 2),
            ],
        ];
    }

    /**
     * Generate certificate issuance report
     */
    protected function generateCertificateIssuanceReport(array $parameters, Carbon $startDate, Carbon $endDate): array
    {
        $query = \DB::table('certificates')->whereBetween('created_at', [$startDate, $endDate]);

        if (isset($parameters['course_id'])) {
            $enrollmentIds = Enrollment::where('course_id', $parameters['course_id'])->pluck('id');
            $query->whereIn('enrollment_id', $enrollmentIds);
        }

        $certificates = $query->get();

        $data = $certificates->map(function ($certificate) {
            $enrollment = Enrollment::find($certificate->enrollment_id);
            
            return [
                'certificate_number' => $certificate->certificate_number,
                'user_name' => $enrollment->user->name ?? 'N/A',
                'course_title' => $enrollment->course->title ?? 'N/A',
                'issued_at' => Carbon::parse($certificate->created_at)->format('Y-m-d H:i:s'),
                'expires_at' => $certificate->expires_at ? Carbon::parse($certificate->expires_at)->format('Y-m-d') : 'Never',
            ];
        });

        return [
            'title' => 'Certificate Issuance Report',
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'data' => $data->toArray(),
            'summary' => [
                'total_certificates' => $certificates->count(),
            ],
        ];
    }

    /**
     * Generate learning hours report
     */
    protected function generateLearningHoursReport(array $parameters, Carbon $startDate, Carbon $endDate): array
    {
        $query = User::query();

        if (isset($parameters['department_id'])) {
            $query->where('department_id', $parameters['department_id']);
        }

        $users = $query->get();

        $data = $users->map(function ($user) use ($startDate, $endDate) {
            $learningTime = $this->analyticsService->getUserAnalytics($user, $startDate, $endDate)['learning_time'];
            
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'department' => $user->department?->name,
                'learning_hours' => round($learningTime, 2),
            ];
        });

        return [
            'title' => 'Learning Hours Report',
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'data' => $data->toArray(),
            'summary' => [
                'total_users' => $users->count(),
                'total_learning_hours' => round($data->sum('learning_hours'), 2),
                'average_learning_hours' => round($data->avg('learning_hours'), 2),
            ],
        ];
    }

    /**
     * Export report to file
     */
    protected function exportReport(Report $report, array $data): string
    {
        $format = $report->file_format;
        $filename = "report_{$report->id}_{$report->report_type}_" . now()->format('Y-m-d_His');

        return match ($format) {
            'pdf' => $this->exportToPDF($data, $filename),
            'excel' => $this->exportToExcel($data, $filename),
            'csv' => $this->exportToCSV($data, $filename),
            default => throw new \InvalidArgumentException("Unknown export format: {$format}"),
        };
    }

    /**
     * Export to PDF
     */
    protected function exportToPDF(array $data, string $filename): string
    {
        $pdf = Pdf::loadView('reports.pdf-template', ['data' => $data]);
        
        $path = "reports/{$filename}.pdf";
        Storage::put($path, $pdf->output());
        
        return $path;
    }

    /**
     * Export to Excel
     */
    protected function exportToExcel(array $data, string $filename): string
    {
        $path = "reports/{$filename}.xlsx";
        
        Excel::store(new ReportExport($data), $path);
        
        return $path;
    }

    /**
     * Export to CSV
     */
    protected function exportToCSV(array $data, string $filename): string
    {
        $path = "reports/{$filename}.csv";
        
        Excel::store(new ReportExport($data), $path, null, \Maatwebsite\Excel\Excel::CSV);
        
        return $path;
    }

    /**
     * Get default report name
     */
    protected function getDefaultReportName(string $type): string
    {
        $names = [
            'user_activity' => 'User Activity Report',
            'course_completion' => 'Course Completion Report',
            'department_performance' => 'Department Performance Report',
            'compliance' => 'Compliance Report',
            'assessment_results' => 'Assessment Results Report',
            'enrollment_summary' => 'Enrollment Summary Report',
            'certificate_issuance' => 'Certificate Issuance Report',
            'learning_hours' => 'Learning Hours Report',
        ];

        return $names[$type] ?? 'Custom Report';
    }

    /**
     * Parse schedule string
     */
    protected function parseSchedule(string $schedule): array
    {
        // Expected format: "daily", "weekly", "monthly", "quarterly"
        return [
            'frequency' => $schedule,
            'enabled' => true,
        ];
    }

    /**
     * Calculate next run time
     */
    protected function calculateNextRunTime(array $scheduleConfig): Carbon
    {
        $frequency = $scheduleConfig['frequency'] ?? 'daily';

        return match ($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            default => now()->addDay(),
        };
    }

    /**
     * Get report download URL
     */
    public function getDownloadUrl(Report $report): ?string
    {
        return $report->getDownloadUrl();
    }

    /**
     * Delete report file
     */
    public function deleteReport(Report $report): bool
    {
        if ($report->file_path && Storage::exists($report->file_path)) {
            Storage::delete($report->file_path);
        }

        return $report->delete();
    }
}
