<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'question_text',
        'question_type',
        'points',
        'order_index',
        'explanation',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'points' => 'decimal:2',
    ];

    // Relationships

    /**
     * Get the assessment this question belongs to
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get the options for this question
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
    }

    /**
     * Get the responses for this question
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AttemptResponse::class);
    }

    // Question Type Checks

    /**
     * Check if question is multiple choice
     */
    public function isMultipleChoice(): bool
    {
        return $this->question_type === 'multiple_choice';
    }

    /**
     * Check if question is true/false
     */
    public function isTrueFalse(): bool
    {
        return $this->question_type === 'true_false';
    }

    /**
     * Check if question is fill in the blank
     */
    public function isFillInBlank(): bool
    {
        return $this->question_type === 'fill_in_blank';
    }

    /**
     * Check if question is essay
     */
    public function isEssay(): bool
    {
        return $this->question_type === 'essay';
    }

    /**
     * Check if question is matching
     */
    public function isMatching(): bool
    {
        return $this->question_type === 'matching';
    }

    /**
     * Check if question is drag and drop
     */
    public function isDragDrop(): bool
    {
        return $this->question_type === 'drag_drop';
    }

    /**
     * Check if question requires manual grading
     */
    public function requiresManualGrading(): bool
    {
        return in_array($this->question_type, ['essay', 'fill_in_blank']);
    }

    /**
     * Get correct options for this question
     */
    public function getCorrectOptions()
    {
        return $this->options()->where('is_correct', true)->get();
    }

    /**
     * Validate a response for this question
     */
    public function validateResponse($response): bool
    {
        if ($this->requiresManualGrading()) {
            return false; // Cannot auto-validate
        }

        if ($this->isMultipleChoice() || $this->isTrueFalse()) {
            $correctOptionIds = $this->getCorrectOptions()->pluck('id')->toArray();
            
            if (is_array($response)) {
                return empty(array_diff($response, $correctOptionIds)) && 
                       empty(array_diff($correctOptionIds, $response));
            }
            
            return in_array($response, $correctOptionIds);
        }

        return false;
    }

    /**
     * Calculate score for a response
     */
    public function calculateScore($response): float
    {
        if ($this->requiresManualGrading()) {
            return 0; // Requires manual grading
        }

        if ($this->validateResponse($response)) {
            return $this->points;
        }

        return 0;
    }
}
