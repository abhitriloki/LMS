<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class AssessmentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'user_id',
        'started_at',
        'submitted_at',
        'time_taken',
        'score',
        'percentage',
        'status',
        'passed',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'passed' => 'boolean',
    ];

    // Relationships

    /**
     * Get the assessment for this attempt
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get the user who made this attempt
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who graded this attempt
     */
    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Get the responses for this attempt
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AttemptResponse::class, 'attempt_id');
    }

    // Query Scopes

    /**
     * Scope to get completed attempts
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get in-progress attempts
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get passed attempts
     */
    public function scopePassed(Builder $query): Builder
    {
        return $query->where('passed', true);
    }

    /**
     * Scope to get failed attempts
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('passed', false);
    }

    // Status Methods

    /**
     * Check if attempt is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if attempt is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if attempt is submitted
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Check if attempt is graded
     */
    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    /**
     * Check if attempt passed
     */
    public function hasPassed(): bool
    {
        return $this->passed === true;
    }

    /**
     * Check if time limit exceeded
     */
    public function isTimeLimitExceeded(): bool
    {
        if (!$this->assessment->hasTimeLimit()) {
            return false;
        }

        $timeLimit = $this->assessment->time_limit * 60; // Convert to seconds
        $elapsed = now()->diffInSeconds($this->started_at);

        return $elapsed > $timeLimit;
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingTime(): ?int
    {
        if (!$this->assessment->hasTimeLimit() || !$this->isInProgress()) {
            return null;
        }

        $timeLimit = $this->assessment->time_limit * 60; // Convert to seconds
        $elapsed = now()->diffInSeconds($this->started_at);
        $remaining = $timeLimit - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Submit the attempt
     */
    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'time_taken' => now()->diffInSeconds($this->started_at),
        ]);
    }

    /**
     * Grade the attempt
     */
    public function grade(float $score, float $percentage, bool $passed, User $gradedBy = null): void
    {
        $this->update([
            'status' => 'graded',
            'score' => $score,
            'percentage' => $percentage,
            'passed' => $passed,
            'graded_by' => $gradedBy?->id,
            'graded_at' => now(),
        ]);
    }

    /**
     * Calculate total score from responses
     */
    public function calculateScore(): float
    {
        return $this->responses()->sum('points_earned');
    }

    /**
     * Calculate percentage score
     */
    public function calculatePercentage(): float
    {
        $totalPoints = $this->assessment->getTotalPoints();
        
        if ($totalPoints == 0) {
            return 0;
        }

        return ($this->calculateScore() / $totalPoints) * 100;
    }
}
