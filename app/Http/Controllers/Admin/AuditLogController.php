<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AuditLogController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display audit logs
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'user_id',
            'event_type',
            'start_date',
            'end_date',
            'auditable_type',
            'auditable_id',
            'per_page'
        ]);

        $logs = $this->auditLogService->getAuditLogs($filters);

        // Get unique event types for filter dropdown
        $eventTypes = \App\Models\AuditLog::select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        return view('admin.audit-logs.index', compact('logs', 'eventTypes', 'filters'));
    }

    /**
     * Show a specific audit log
     */
    public function show(int $id)
    {
        $log = \App\Models\AuditLog::with(['user', 'auditable'])->findOrFail($id);

        return view('admin.audit-logs.show', compact('log'));
    }

    /**
     * Export audit logs to CSV
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'user_id',
            'event_type',
            'start_date',
            'end_date',
            'auditable_type',
            'auditable_id'
        ]);

        $csv = $this->auditLogService->exportToCsv($filters);

        $filename = 'audit-logs-' . now()->format('Y-m-d-His') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get audit logs for a specific user
     */
    public function userLogs(int $userId)
    {
        $logs = $this->auditLogService->getAuditLogs(['user_id' => $userId]);
        $user = \App\Models\User::findOrFail($userId);

        return view('admin.audit-logs.user', compact('logs', 'user'));
    }

    /**
     * Get audit logs for a specific model
     */
    public function modelLogs(Request $request)
    {
        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');

        $logs = $this->auditLogService->getAuditLogs([
            'auditable_type' => $modelType,
            'auditable_id' => $modelId
        ]);

        return view('admin.audit-logs.model', compact('logs', 'modelType', 'modelId'));
    }
}
