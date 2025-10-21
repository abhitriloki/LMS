<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PathAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_path_id',
        'adjustment_reason',
        'previous_path_data',
        'new_path_data',
    ];

    protected $casts = [
        'previous_path_data' => 'array',
        'new_path_data' => 'array',
    ];

    /**
     * Get the learning path this adjustment belongs to
     */
    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(AILearningPath::class, 'learning_path_id');
    }
}
