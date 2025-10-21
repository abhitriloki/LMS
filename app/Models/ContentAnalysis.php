<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentAnalysis extends Model
{
    use HasFactory;

    protected $table = 'content_analysis';

    protected $fillable = [
        'course_id',
        'analysis_type',
        'readability_score',
        'engagement_score',
        'overall_score',
        'complexity_level',
        'content_gaps',
        'suggestions',
        'accessibility_issues',
        'is_current',
        'analyzed_at',
    ];

    protected $casts = [
        'readability_score' => 'decimal:2',
        'engagement_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'content_gaps' => 'array',
        'suggestions' => 'array',
        'accessibility_issues' => 'array',
        'is_current' => 'boolean',
        'analyzed_at' => 'datetime',
    ];

    /**
     * Get the course this analysis is for
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Check if content has gaps
     */
    public function hasGaps(): bool
    {
        return !empty($this->content_gaps);
    }

    /**
     * Check if content has accessibility issues
     */
    public function hasAccessibilityIssues(): bool
    {
        return !empty($this->accessibility_issues);
    }

    /**
     * Get overall score
     */
    public function getOverallScore(): float
    {
        return $this->overall_score ?? 0;
    }

    /**
     * Get score grade (A, B, C, D, F)
     */
    public function getScoreGrade(): string
    {
        $score = $this->overall_score ?? 0;

        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Get total number of issues
     */
    public function getTotalIssues(): int
    {
        $gaps = $this->content_gaps ?? [];
        $accessibility = $this->accessibility_issues ?? [];
        
        $gapCount = count($gaps['missing_topics'] ?? []) + 
                    count($gaps['progression_gaps'] ?? []) +
                    count($gaps['needs_more_depth'] ?? []) +
                    count($gaps['missing_prerequisites'] ?? []);
        
        return $gapCount + count($accessibility);
    }
}
