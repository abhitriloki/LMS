<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AIRecommendation extends Model
{
    use HasFactory;

    protected $table = 'ai_recommendations';

    protected $fillable = [
        'user_id',
        'course_id',
        'recommendation_type',
        'score',
        'reasoning',
        'status',
        'feedback',
        'expires_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user this recommendation is for
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the recommended course
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // Query Scopes

    /**
     * Scope to get active recommendations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope to get accepted recommendations
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get rejected recommendations
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    // Status Methods

    /**
     * Check if recommendation is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if recommendation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Accept the recommendation
     */
    public function accept(string $feedback = null): void
    {
        $this->update([
            'status' => 'accepted',
            'feedback' => $feedback,
        ]);
    }

    /**
     * Reject the recommendation
     */
    public function reject(string $feedback = null): void
    {
        $this->update([
            'status' => 'rejected',
            'feedback' => $feedback,
        ]);
    }
}
