<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order_index',
    ];

    /**
     * Get the course this module belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lessons in this module
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class, 'module_id')->orderBy('order_index');
    }

    /**
     * Get total duration of all lessons in this module
     */
    public function getTotalDuration(): int
    {
        return $this->lessons()->sum('duration');
    }

    /**
     * Get total number of lessons in this module
     */
    public function getLessonsCount(): int
    {
        return $this->lessons()->count();
    }
}
