<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an audit event
     */
    public function log(
        string $eventType,
        ?Model $auditable = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?User $user = null
    ): AuditLog {
        $user = $user ?? Auth::user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a model creation
     */
    public function logCreated(Model $model, ?string $description = null): AuditLog
    {
        return $this->log(
            eventType: 'created',
            auditable: $model,
            description: $description ?? class_basename($model) . ' created',
            newValues: $this->getRelevantAttributes($model)
        );
    }

    /**
     * Log a model update
     */
    public function logUpdated(Model $model, array $oldValues, ?string $description = null): AuditLog
    {
        return $this->log(
            eventType: 'updated',
            auditable: $model,
            description: $description ?? class_basename($model) . ' updated',
            oldValues: $oldValues,
            newValues: $this->getRelevantAttributes($model)
        );
    }

    /**
     * Log a model deletion
     */
    public function logDeleted(Model $model, ?string $description = null): AuditLog
    {
        return $this->log(
            eventType: 'deleted',
            auditable: $model,
            description: $description ?? class_basename($model) . ' deleted',
            oldValues: $this->getRelevantAttributes($model)
        );
    }

    /**
     * Log a model view/access
     */
    public function logViewed(Model $model, ?string $description = null): AuditLog
    {
        return $this->log(
            eventType: 'viewed',
            auditable: $model,
            description: $description ?? class_basename($model) . ' viewed'
        );
    }

    /**
     * Log a login attempt
     */
    public function logLogin(User $user, bool $successful = true): AuditLog
    {
        return $this->log(
            eventType: $successful ? 'login_success' : 'login_failed',
            auditable: $user,
            description: $successful ? 'User logged in successfully' : 'Failed login attempt',
            user: $user
        );
    }

    /**
     * Log a logout
     */
    public function logLogout(User $user): AuditLog
    {
        return $this->log(
            eventType: 'logout',
            auditable: $user,
            description: 'User logged out',
            user: $user
        );
    }

    /**
     * Log a permission change
     */
    public function logPermissionChange(User $user, string $oldRole, string $newRole): AuditLog
    {
        return $this->log(
            eventType: 'permission_changed',
            auditable: $user,
            description: "User role changed from {$oldRole} to {$newRole}",
            oldValues: ['role' => $oldRole],
            newValues: ['role' => $newRole]
        );
    }

    /**
     * Log a security event
     */
    public function logSecurityEvent(string $eventType, string $description, ?array $metadata = null): AuditLog
    {
        return $this->log(
            eventType: 'security_' . $eventType,
            description: $description,
            metadata: $metadata
        );
    }

    /**
     * Get audit logs with filters
     */
    public function getAuditLogs(array $filters = [])
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['event_type'])) {
            $query->ofType($filters['event_type']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['auditable_type'])) {
            $query->forModel($filters['auditable_type'], $filters['auditable_id'] ?? null);
        }

        return $query->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get relevant attributes from model (excluding sensitive data)
     */
    protected function getRelevantAttributes(Model $model): array
    {
        $attributes = $model->getAttributes();
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
        
        foreach ($sensitiveFields as $field) {
            unset($attributes[$field]);
        }

        return $attributes;
    }

    /**
     * Export audit logs to CSV
     */
    public function exportToCsv(array $filters = []): string
    {
        $logs = $this->getAuditLogs($filters)->items();
        
        $csv = "ID,User,Event Type,Description,IP Address,Created At\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->user?->name ?? 'System',
                $log->event_type,
                str_replace(["\n", "\r", ","], [" ", " ", ";"], $log->description ?? ''),
                $log->ip_address,
                $log->created_at->format('Y-m-d H:i:s')
            );
        }

        return $csv;
    }
}
