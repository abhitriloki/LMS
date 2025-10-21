<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'content_type',
        'content_path',
        'content_url',
        'duration',
        'order_index',
        'is_downloadable',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_downloadable' => 'boolean',
    ];

    /**
     * Get the module this lesson belongs to
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /**
     * Get the progress records for this lesson
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }

    /**
     * Check if lesson is video content
     */
    public function isVideo(): bool
    {
        return $this->content_type === 'video';
    }

    /**
     * Check if lesson is PDF content
     */
    public function isPdf(): bool
    {
        return $this->content_type === 'pdf';
    }

    /**
     * Check if lesson is SCORM content
     */
    public function isScorm(): bool
    {
        return $this->content_type === 'scorm';
    }

    /**
     * Check if lesson is text content
     */
    public function isText(): bool
    {
        return $this->content_type === 'text';
    }

    /**
     * Get the course this lesson belongs to through module
     */
    public function course()
    {
        return $this->module->course;
    }
}
