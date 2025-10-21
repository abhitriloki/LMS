<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ChatbotConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'status',
        'context_data',
        'started_at',
        'ended_at',
        'rating',
        'feedback',
    ];

    protected $casts = [
        'context_data' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the user this conversation belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages in this conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id')->orderBy('created_at');
    }

    // Query Scopes

    /**
     * Scope to get active conversations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get ended conversations
     */
    public function scopeEnded(Builder $query): Builder
    {
        return $query->where('status', 'ended');
    }

    // Status Methods

    /**
     * Check if conversation is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if conversation has ended
     */
    public function hasEnded(): bool
    {
        return $this->status === 'ended';
    }

    /**
     * End the conversation
     */
    public function end(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
    }

    /**
     * Rate the conversation
     */
    public function rate(int $rating, string $feedback = null): void
    {
        $this->update([
            'rating' => $rating,
            'feedback' => $feedback,
        ]);
    }

    /**
     * Add a message to the conversation
     */
    public function addMessage(string $role, string $content, array $metadata = []): ChatbotMessage
    {
        return $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get the last message in the conversation
     */
    public function getLastMessage(): ?ChatbotMessage
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Get message count
     */
    public function getMessageCount(): int
    {
        return $this->messages()->count();
    }
}
