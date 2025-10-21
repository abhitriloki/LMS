<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AILearningPath extends Model
{
    use HasFactory;

    protected $table = 'ai_learning_paths';

    protected $fillable = [
        'user_id',
        'target_role',
        'current_skills',
        'target_skills',
        'path_data',
        'estimated_duration',
        'status',
        'progress_percentage',
        'started_at',
        'completed_at',
        'last_adjusted_at',
    ];

    protected $casts = [
        'current_skills' => 'array',
        'target_skills' => 'array',
        'path_data' => 'array',
        'progress_percentage' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_adjusted_at' => 'datetime',
    ];

    /**
     * Get the user this learning path is for
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get path adjustments
     */
    public function adjustments()
    {
        return $this->hasMany(PathAdjustment::class, 'learning_path_id');
    }

    // Query Scopes

    /**
     * Scope to get active learning paths
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get completed learning paths
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get paused learning paths
     */
    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', 'paused');
    }

    // Status Methods

    /**
     * Check if learning path is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if learning path is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if learning path is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Start the learning path
     */
    public function start(): void
    {
        $this->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Pause the learning path
     */
    public function pause(): void
    {
        $this->update([
            'status' => 'paused',
        ]);
    }

    /**
     * Resume the learning path
     */
    public function resume(): void
    {
        $this->update([
            'status' => 'active',
        ]);
    }

    /**
     * Complete the learning path
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(float $percentage): void
    {
        $data = [
            'progress_percentage' => $percentage,
        ];

        if ($percentage >= 100) {
            $data['status'] = 'completed';
            $data['completed_at'] = now();
        }

        $this->update($data);
    }

    /**
     * Adjust the learning path
     */
    public function adjust(array $newPathData, string $reason): void
    {
        $this->update([
            'path_data' => $newPathData,
            'last_adjusted_at' => now(),
        ]);

        // Record the adjustment
        $this->adjustments()->create([
            'adjustment_reason' => $reason,
            'previous_path_data' => $this->getOriginal('path_data'),
            'new_path_data' => $newPathData,
        ]);
    }
}
