<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIGradingResult extends Model
{
    use HasFactory;

    protected $table = 'ai_grading_results';

    protected $fillable = [
        'attempt_response_id',
        'ai_score',
        'ai_feedback',
        'confidence_score',
        'rubric_scores',
        'flagged_for_review',
        'review_reason',
        'human_score',
        'human_feedback',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'ai_score' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'rubric_scores' => 'array',
        'flagged_for_review' => 'boolean',
        'human_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the attempt response this grading is for
     */
    public function attemptResponse(): BelongsTo
    {
        return $this->belongsTo(AttemptResponse::class);
    }

    /**
     * Get the user who reviewed this grading
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Check if grading is flagged for review
     */
    public function isFlaggedForReview(): bool
    {
        return $this->flagged_for_review === true;
    }

    /**
     * Check if grading has been reviewed by human
     */
    public function hasBeenReviewed(): bool
    {
        return !is_null($this->reviewed_at);
    }

    /**
     * Check if confidence is low
     */
    public function hasLowConfidence(float $threshold = 0.7): bool
    {
        return $this->confidence_score < $threshold;
    }

    /**
     * Get final score (human score if reviewed, otherwise AI score)
     */
    public function getFinalScore(): float
    {
        return $this->human_score ?? $this->ai_score;
    }

    /**
     * Get final feedback (human feedback if reviewed, otherwise AI feedback)
     */
    public function getFinalFeedback(): string
    {
        return $this->human_feedback ?? $this->ai_feedback;
    }

    /**
     * Flag for human review
     */
    public function flagForReview(string $reason): void
    {
        $this->update([
            'flagged_for_review' => true,
            'review_reason' => $reason,
        ]);
    }

    /**
     * Review and override AI grading
     */
    public function review(User $reviewer, float $humanScore, string $humanFeedback = null): void
    {
        $this->update([
            'human_score' => $humanScore,
            'human_feedback' => $humanFeedback,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'flagged_for_review' => false,
        ]);
    }
}
