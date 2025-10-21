<?php

namespace App\Traits;

use App\Services\AuditLogService;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            if (config('audit.enabled', true)) {
                app(AuditLogService::class)->logCreated($model);
            }
        });

        static::updated(function ($model) {
            if (config('audit.enabled', true)) {
                $oldValues = $model->getOriginal();
                $changes = $model->getChanges();
                
                // Only log if there are actual changes
                if (!empty($changes)) {
                    app(AuditLogService::class)->logUpdated($model, $oldValues);
                }
            }
        });

        static::deleted(function ($model) {
            if (config('audit.enabled', true)) {
                app(AuditLogService::class)->logDeleted($model);
            }
        });
    }
}
