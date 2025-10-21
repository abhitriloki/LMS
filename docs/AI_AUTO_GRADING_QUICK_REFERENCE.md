# AI Auto-Grading System - Quick Reference

## Quick Start

### Grade an Essay Response

```php
use App\Services\AI\AIGradingService;

$service = app(AIGradingService::class);
$result = $service->gradeEssay($response, $rubric);
```

### Override AI Grading

```php
use App\Services\AttemptService;

$service = app(AttemptService::class);
$service->overrideAIGrading($response, $instructor, $newScore, $feedback);
```

## Key Classes

### AIGradingService

**Location**: `app/Services/AI/AIGradingService.php`

**Main Methods**:
```php
gradeEssay(AttemptResponse $response, array $rubric = []): AIGradingResult
calculateConfidence(array $analysis): float
generateFeedback(array $analysis, string $response): string
checkPlagiarism(string $response, array $otherResponses): array
getFlaggedResponses(int $assessmentId = null): Collection
```

### GradingReviewController

**Location**: `app/Http/Controllers/Admin/GradingReviewController.php`

**Routes**:
- `GET /admin/grading/review` - Review queue
- `GET /admin/grading/review/{id}` - Review detail
- `PUT /admin/grading/review/{id}` - Override grading
- `POST /admin/grading/review/{id}/accept` - Accept AI grading
- `GET /admin/grading/statistics` - Statistics

## Models

### AIGradingResult

**Fields**:
- `ai_score` - AI-assigned score
- `ai_feedback` - AI-generated feedback
- `confidence_score` - Confidence level (0-1)
- `rubric_scores` - Scores per rubric criterion
- `flagged_for_review` - Whether flagged for human review
- `review_reason` - Why flagged
- `human_score` - Instructor override score
- `human_feedback` - Instructor feedback
- `reviewed_by` - Instructor who reviewed
- `reviewed_at` - Review timestamp

**Methods**:
```php
isFlaggedForReview(): bool
hasBeenReviewed(): bool
hasLowConfidence(float $threshold = 0.7): bool
getFinalScore(): float
getFinalFeedback(): string
flagForReview(string $reason): void
review(User $reviewer, float $score, string $feedback = null): void
```

## Confidence Scoring

| Score | Status | Action |
|-------|--------|--------|
| ≥ 0.7 | High Confidence | Auto-grade |
| < 0.7 | Low Confidence | Flag for review |

**Adjustments**:
- Many weaknesses (>3): -0.1
- Many strengths (>2): +0.05

## Rubric Format

```php
$rubric = [
    'content' => [
        'description' => 'Quality and relevance of content',
        'points' => 40
    ],
    'organization' => [
        'description' => 'Structure and logical flow',
        'points' => 30
    ],
    'grammar' => [
        'description' => 'Grammar, spelling, and mechanics',
        'points' => 30
    ]
];
```

Store in question metadata:
```php
$question->metadata = ['rubric' => $rubric];
```

## Feedback Structure

```
[Overall Assessment]

**Strengths:**
- Strength 1
- Strength 2

**Areas for Improvement:**
- Weakness 1
- Weakness 2

**Suggestions:**
- Suggestion 1
- Suggestion 2
```

## Common Tasks

### Configure Confidence Threshold

```php
$service = app(AIGradingService::class);
$service->setLowConfidenceThreshold(0.6); // More lenient
```

### Get Flagged Responses for Assessment

```php
$service = app(AIGradingService::class);
$flagged = $service->getFlaggedResponses($assessmentId);
```

### Batch Grade Essays

```php
$service = app(AIGradingService::class);
$results = $service->batchGradeEssays($responses, $rubric);
```

### Check Plagiarism

```php
$service = app(AIGradingService::class);
$similarities = $service->checkPlagiarism($response, $otherResponses);
```

## View Components

### Review Queue
**File**: `resources/views/admin/grading/review-index.blade.php`
- Lists flagged responses
- Filter by assessment/confidence
- Quick accept or review

### Review Detail
**File**: `resources/views/admin/grading/review-show.blade.php`
- Full response details
- AI grading results
- Override form
- Accept button

### Statistics
**File**: `resources/views/admin/grading/statistics.blade.php`
- Total graded count
- Pending review count
- Average confidence
- Low confidence count

## Testing

### Run Unit Tests
```bash
php artisan test --filter=AIGradingServiceTest
```

### Run Feature Tests
```bash
php artisan test --filter=AIGradingTest
```

### Run All AI Grading Tests
```bash
php artisan test tests/Unit/Services/AI/AIGradingServiceTest.php
php artisan test tests/Feature/AI/AIGradingTest.php
```

## Troubleshooting

### AI Grading Not Working
1. Check AI service configuration
2. Verify question type is 'essay'
3. Check logs: `storage/logs/laravel.log`
4. Ensure AI service is available

### All Responses Flagged
1. Check confidence threshold setting
2. Review AI prompt quality
3. Verify rubric is properly formatted
4. Check AI service response format

### Scores Not Updating
1. Verify `overrideAIGrading()` is called
2. Check `recalculateAttemptScore()` execution
3. Ensure database transactions complete
4. Refresh model instances

## Configuration

### Environment Variables
```env
OPENAI_API_KEY=your_api_key_here
```

### Service Configuration
```php
// config/services.php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'rate_limits' => [
        'text-analysis' => 60, // per minute
    ],
],
```

## Best Practices

1. **Always provide rubrics** for consistent grading
2. **Review flagged responses** promptly
3. **Monitor confidence scores** to adjust thresholds
4. **Provide clear feedback** when overriding
5. **Use batch operations** for efficiency
6. **Track override patterns** to improve AI
7. **Set appropriate thresholds** for your use case

## API Endpoints

### List Flagged Responses
```
GET /admin/grading/review
Query params: assessment_id, max_confidence
```

### View Response Detail
```
GET /admin/grading/review/{gradingResult}
```

### Override Grading
```
PUT /admin/grading/review/{gradingResult}
Body: { human_score, human_feedback }
```

### Accept AI Grading
```
POST /admin/grading/review/{gradingResult}/accept
```

### Get Statistics
```
GET /admin/grading/statistics
Query params: assessment_id (optional)
```

## Related Files

- Service: `app/Services/AI/AIGradingService.php`
- Controller: `app/Http/Controllers/Admin/GradingReviewController.php`
- Model: `app/Models/AIGradingResult.php`
- Views: `resources/views/admin/grading/`
- Tests: `tests/Unit/Services/AI/AIGradingServiceTest.php`
- Tests: `tests/Feature/AI/AIGradingTest.php`
- Routes: `routes/web.php` (search for "grading.review")
