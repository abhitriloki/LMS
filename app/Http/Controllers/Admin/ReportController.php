<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display report management page
     */
    public function index(Request $request)
    {
        $query = Report::with('generatedBy')->latest();

        if ($request->has('type')) {
            $query->ofType($request->input('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $reports = $query->paginate(20);

        return view('admin.reports.index', compact('reports'));
    }

    /**
     * Show report builder form
     */
    public function create()
    {
        $reportTypes = [
            'user_activity' => 'User Activity Report',
            'course_completion' => 'Course Completion Report',
            'department_performance' => 'Department Performance Report',
            'compliance' => 'Compliance Report',
            'assessment_results' => 'Assessment Results Report',
            'enrollment_summary' => 'Enrollment Summary Report',
            'certificate_issuance' => 'Certificate Issuance Report',
            'learning_hours' => 'Learning Hours Report',
        ];

        return view('admin.reports.create', compact('reportTypes'));
    }

    /**
     * Generate a new report
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'report_type' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'course_id' => 'nullable|exists:courses,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:course_categories,id',
            'assessment_id' => 'nullable|exists:assessments,id',
            'role' => 'nullable|string',
        ]);

        try {
            $report = $this->reportService->generateReport(
                $validated['report_type'],
                $validated,
                $request->user()
            );

            return redirect()
                ->route('admin.reports.show', $report)
                ->with('success', 'Report generated successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Display a specific report
     */
    public function show(Report $report)
    {
        $report->load('generatedBy');

        return view('admin.reports.show', compact('report'));
    }

    /**
     * Schedule a report
     */
    public function schedule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'report_type' => 'required|string',
            'format' => 'required|in:pdf,excel,csv',
            'schedule' => 'required|in:daily,weekly,monthly,quarterly',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'course_id' => 'nullable|exists:courses,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:course_categories,id',
            'assessment_id' => 'nullable|exists:assessments,id',
            'role' => 'nullable|string',
        ]);

        try {
            $report = $this->reportService->scheduleReport(
                $validated['report_type'],
                $validated,
                $validated['schedule'],
                $request->user()
            );

            return redirect()
                ->route('admin.reports.index')
                ->with('success', 'Report scheduled successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to schedule report: ' . $e->getMessage());
        }
    }

    /**
     * Download a report
     */
    public function download(Report $report)
    {
        if (!$report->isCompleted() || !$report->file_path) {
            abort(404, 'Report file not found');
        }

        if (!Storage::exists($report->file_path)) {
            abort(404, 'Report file not found');
        }

        $filename = basename($report->file_path);

        return Storage::download($report->file_path, $filename);
    }

    /**
     * Delete a report
     */
    public function destroy(Report $report)
    {
        try {
            $this->reportService->deleteReport($report);

            return redirect()
                ->route('admin.reports.index')
                ->with('success', 'Report deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete report: ' . $e->getMessage());
        }
    }

    /**
     * Get scheduled reports
     */
    public function scheduled(Request $request)
    {
        $reports = Report::scheduled()
            ->with('generatedBy')
            ->latest()
            ->paginate(20);

        return view('admin.reports.scheduled', compact('reports'));
    }

    /**
     * Update scheduled report
     */
    public function updateSchedule(Request $request, Report $report)
    {
        $validated = $request->validate([
            'schedule' => 'required|in:daily,weekly,monthly,quarterly',
            'enabled' => 'boolean',
        ]);

        $scheduleConfig = [
            'frequency' => $validated['schedule'],
            'enabled' => $validated['enabled'] ?? true,
        ];

        $report->update([
            'schedule_config' => $scheduleConfig,
            'scheduled' => $validated['enabled'] ?? true,
        ]);

        $report->updateNextRunTime();

        return back()->with('success', 'Schedule updated successfully');
    }

    /**
     * Disable scheduled report
     */
    public function disableSchedule(Report $report)
    {
        $report->update(['scheduled' => false]);

        return back()->with('success', 'Report schedule disabled');
    }

    /**
     * Enable scheduled report
     */
    public function enableSchedule(Report $report)
    {
        $report->update(['scheduled' => true]);
        $report->updateNextRunTime();

        return back()->with('success', 'Report schedule enabled');
    }

    /**
     * Get report preview data (AJAX)
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'course_id' => 'nullable|exists:courses,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:course_categories,id',
            'assessment_id' => 'nullable|exists:assessments,id',
            'role' => 'nullable|string',
        ]);

        try {
            // Generate preview data (limited to 10 rows)
            $data = $this->reportService->generateReportData(
                $validated['report_type'],
                array_merge($validated, ['limit' => 10])
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate preview: ' . $e->getMessage(),
            ], 400);
        }
    }
}
