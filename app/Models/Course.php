<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;

class Course extends Model
{
    use HasFactory, Searchable, \App\Traits\Auditable;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'category_id',
        'difficulty_level',
        'estimated_duration',
        'thumbnail',
        'preview_video',
        'price',
        'is_mandatory',
        'is_published',
        'is_featured',
        'prerequisites',
        'learning_objectives',
        'target_audience',
        'language',
        'tags',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'learning_objectives' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_mandatory' => 'boolean',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
    ];

    // Relationships

    /**
     * Get the category this course belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    /**
     * Get the user who created this course
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the modules for this course
     */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('order_index');
    }

    /**
     * Get all lessons for this course through modules
     */
    public function lessons(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(CourseLesson::class, CourseModule::class);
    }

    /**
     * Get the enrollments for this course
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the assessments for this course
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Get AI recommendations for this course
     */
    public function recommendations(): HasMany
    {
        return $this->hasMany(AIRecommendation::class);
    }

    /**
     * Get content analysis for this course
     */
    public function contentAnalysis(): HasMany
    {
        return $this->hasMany(ContentAnalysis::class);
    }

    // Query Scopes

    /**
     * Scope to get only published courses
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get only mandatory courses
     */
    public function scopeMandatory(Builder $query): Builder
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope to get only featured courses
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by difficulty level
     */
    public function scopeDifficulty(Builder $query, string $level): Builder
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Scope to filter by category
     */
    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to search courses by title or description
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('short_description', 'like', "%{$search}%");
        });
    }

    // Helper Methods

    /**
     * Check if the course has prerequisites
     */
    public function hasPrerequisites(): bool
    {
        return !empty($this->prerequisites);
    }

    /**
     * Get total number of lessons in the course
     */
    public function getTotalLessonsCount(): int
    {
        return $this->modules()->withCount('lessons')->get()->sum('lessons_count');
    }

    /**
     * Get total duration of all lessons
     */
    public function getTotalDuration(): int
    {
        $totalDuration = 0;
        foreach ($this->modules as $module) {
            $totalDuration += $module->lessons()->sum('duration');
        }
        return $totalDuration;
    }

    /**
     * Check if user is enrolled in this course
     */
    public function isEnrolledBy(User $user): bool
    {
        return $this->enrollments()->where('user_id', $user->id)->exists();
    }

    // Scout Searchable Configuration

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'category_id' => $this->category_id,
            'difficulty_level' => $this->difficulty_level,
            'estimated_duration' => $this->estimated_duration,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'tags' => $this->tags ?? [],
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_published;
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'courses';
    }
}
