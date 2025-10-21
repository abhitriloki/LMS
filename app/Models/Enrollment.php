<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'course_enrollments';

    protected $fillable = [
        'course_id',
        'user_id',
        'enrollment_type',
        'enrolled_by',
        'enrollment_date',
        'deadline',
        'status',
        'completion_date',
        'final_score',
        'certificate_id',
        'progress_percentage',
        'last_accessed_at',
    ];

    protected $casts = [
        'enrollment_date' => 'datetime',
        'deadline' => 'datetime',
        'completion_date' => 'datetime',
        'last_accessed_at' => 'datetime',
        'final_score' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
    ];

    // Relationships

    /**
     * Get the course for this enrollment
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user enrolled in the course
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who enrolled this user
     */
    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    /**
     * Get the certificate for this enrollment
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * Get lesson progress records for this enrollment
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    // Query Scopes

    /**
     * Scope to get active enrollments
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get completed enrollments
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get enrollments with upcoming deadlines
     */
    public function scopeUpcomingDeadline(Builder $query, int $days = 7): Builder
    {
        return $query->where('deadline', '<=', now()->addDays($days))
                     ->where('deadline', '>=', now())
                     ->where('status', '!=', 'completed');
    }

    /**
     * Scope to get overdue enrollments
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('deadline', '<', now())
                     ->where('status', '!=', 'completed');
    }

    // Helper Methods

    /**
     * Check if enrollment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if enrollment is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if enrollment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !$this->isCompleted();
    }

    /**
     * Mark enrollment as completed
     */
    public function markAsCompleted(float $finalScore = null): void
    {
        $this->update([
            'status' => 'completed',
            'completion_date' => now(),
            'final_score' => $finalScore,
            'progress_percentage' => 100,
        ]);
    }

    /**
     * Update progress percentage
     */
    public function updateProgress(float $percentage): void
    {
        $this->update([
            'progress_percentage' => $percentage,
            'last_accessed_at' => now(),
        ]);
    }
}
