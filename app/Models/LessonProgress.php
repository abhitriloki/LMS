<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_progress';

    protected $fillable = [
        'enrollment_id',
        'lesson_id',
        'status',
        'progress_percentage',
        'time_spent',
        'last_position',
        'completed_at',
        'first_accessed_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'completed_at' => 'datetime',
        'first_accessed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the enrollment this progress belongs to
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the lesson this progress is for
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    /**
     * Check if lesson is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if lesson is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Mark lesson as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
            'last_accessed_at' => now(),
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(float $percentage, int $position = null): void
    {
        $data = [
            'progress_percentage' => $percentage,
            'last_accessed_at' => now(),
        ];

        if ($position !== null) {
            $data['last_position'] = $position;
        }

        if (!$this->first_accessed_at) {
            $data['first_accessed_at'] = now();
        }

        if ($percentage >= 100) {
            $data['status'] = 'completed';
            $data['completed_at'] = now();
        } elseif ($percentage > 0) {
            $data['status'] = 'in_progress';
        }

        $this->update($data);
    }
}
