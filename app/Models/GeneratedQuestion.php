<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class GeneratedQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'explanation',
        'points',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'question_id',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
        'points' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the job this question was generated from
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(AIQuestionJob::class, 'job_id');
    }

    /**
     * Get the user who reviewed this question
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the final question if approved
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    // Query Scopes

    /**
     * Scope to get pending questions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved questions
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected questions
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    // Status Methods

    /**
     * Check if question is pending review
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if question is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if question is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the question
     */
    public function approve(User $reviewer, string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Reject the question
     */
    public function reject(User $reviewer, string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Link to final question after approval
     */
    public function linkToQuestion(Question $question): void
    {
        $this->update([
            'question_id' => $question->id,
        ]);
    }
}
