<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'response_data',
        'is_correct',
        'points_earned',
        'feedback',
        'ai_grading_result_id',
    ];

    protected $casts = [
        'response_data' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
    ];

    /**
     * Get the attempt this response belongs to
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }

    /**
     * Get the question this response is for
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the AI grading result for this response
     */
    public function aiGradingResult(): BelongsTo
    {
        return $this->belongsTo(AIGradingResult::class);
    }

    /**
     * Get the AI grading result (alias for convenience)
     */
    public function aiGrading(): BelongsTo
    {
        return $this->aiGradingResult();
    }

    /**
     * Check if response is correct
     */
    public function isCorrect(): bool
    {
        return $this->is_correct === true;
    }

    /**
     * Check if response was graded by AI
     */
    public function wasGradedByAI(): bool
    {
        return !is_null($this->ai_grading_result_id);
    }

    /**
     * Grade the response
     */
    public function grade(bool $isCorrect, float $pointsEarned, string $feedback = null): void
    {
        $this->update([
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'feedback' => $feedback,
        ]);
    }
}
