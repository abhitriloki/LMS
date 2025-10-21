<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'report_type',
        'parameters',
        'generated_by',
        'generated_at',
        'file_path',
        'file_format',
        'status',
        'scheduled',
        'schedule_config',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'schedule_config' => 'array',
        'generated_at' => 'datetime',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'scheduled' => 'boolean',
    ];

    /**
     * Get the user who generated this report
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Query Scopes

    /**
     * Scope to get scheduled reports
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('scheduled', true);
    }

    /**
     * Scope to get reports by type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope to get completed reports
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending reports
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed reports
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get reports due for generation
     */
    public function scopeDueForGeneration(Builder $query): Builder
    {
        return $query->where('scheduled', true)
                     ->where('next_run_at', '<=', now());
    }

    // Status Methods

    /**
     * Check if report is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if report is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if report generation failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if report is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->scheduled === true;
    }

    /**
     * Mark report as completed
     */
    public function markAsCompleted(string $filePath): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'generated_at' => now(),
            'last_run_at' => now(),
        ]);

        // Update next run time if scheduled
        if ($this->isScheduled()) {
            $this->updateNextRunTime();
        }
    }

    /**
     * Mark report as failed
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'last_run_at' => now(),
        ]);
    }

    /**
     * Update next run time based on schedule config
     */
    public function updateNextRunTime(): void
    {
        if (!$this->isScheduled() || !$this->schedule_config) {
            return;
        }

        $frequency = $this->schedule_config['frequency'] ?? 'daily';
        $nextRun = now();

        switch ($frequency) {
            case 'daily':
                $nextRun = now()->addDay();
                break;
            case 'weekly':
                $nextRun = now()->addWeek();
                break;
            case 'monthly':
                $nextRun = now()->addMonth();
                break;
            case 'quarterly':
                $nextRun = now()->addMonths(3);
                break;
        }

        $this->update(['next_run_at' => $nextRun]);
    }

    /**
     * Get download URL
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->isCompleted() || !$this->file_path) {
            return null;
        }

        return route('reports.download', $this->id);
    }
}
