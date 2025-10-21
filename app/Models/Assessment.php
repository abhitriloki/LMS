<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'instructions',
        'passing_score',
        'time_limit',
        'max_attempts',
        'randomize_questions',
        'randomize_options',
        'show_results',
        'show_correct_answers',
        'allow_review',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'passing_score' => 'decimal:2',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
        'show_results' => 'boolean',
        'show_correct_answers' => 'boolean',
        'allow_review' => 'boolean',
        'is_published' => 'boolean',
    ];

    // Relationships

    /**
     * Get the course this assessment belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the questions for this assessment
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the attempts for this assessment
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    /**
     * Get the user who created this assessment
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Query Scopes

    /**
     * Scope to get only published assessments
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    // Configuration Methods

    /**
     * Check if assessment has a time limit
     */
    public function hasTimeLimit(): bool
    {
        return $this->time_limit > 0;
    }

    /**
     * Check if assessment has attempt limit
     */
    public function hasAttemptLimit(): bool
    {
        return $this->max_attempts > 0;
    }

    /**
     * Check if questions should be randomized
     */
    public function shouldRandomizeQuestions(): bool
    {
        return $this->randomize_questions;
    }

    /**
     * Check if options should be randomized
     */
    public function shouldRandomizeOptions(): bool
    {
        return $this->randomize_options;
    }

    /**
     * Check if results should be shown
     */
    public function shouldShowResults(): bool
    {
        return $this->show_results;
    }

    /**
     * Check if correct answers should be shown
     */
    public function shouldShowCorrectAnswers(): bool
    {
        return $this->show_correct_answers;
    }

    /**
     * Check if review is allowed
     */
    public function allowsReview(): bool
    {
        return $this->allow_review;
    }

    /**
     * Get total number of questions
     */
    public function getTotalQuestions(): int
    {
        return $this->questions()->count();
    }

    /**
     * Get total points for the assessment
     */
    public function getTotalPoints(): float
    {
        return $this->questions()->sum('points');
    }

    /**
     * Check if user can attempt this assessment
     */
    public function canUserAttempt(User $user): bool
    {
        if (!$this->is_published) {
            return false;
        }

        if (!$this->hasAttemptLimit()) {
            return true;
        }

        $attemptCount = $this->attempts()
            ->where('user_id', $user->id)
            ->count();

        return $attemptCount < $this->max_attempts;
    }

    /**
     * Get user's remaining attempts
     */
    public function getRemainingAttempts(User $user): ?int
    {
        if (!$this->hasAttemptLimit()) {
            return null;
        }

        $attemptCount = $this->attempts()
            ->where('user_id', $user->id)
            ->count();

        return max(0, $this->max_attempts - $attemptCount);
    }
}
