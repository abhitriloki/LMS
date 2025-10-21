# AI Auto-Grading System - Testing Guide

## Overview

This guide covers testing the AI Auto-Grading System, including unit tests, feature tests, and manual testing procedures.

## Running Tests

### All AI Grading Tests

```bash
# Run all AI grading tests
php artisan test --filter=AIGrading

# Run with coverage
php artisan test --filter=AIGrading --coverage
```

### Unit Tests Only

```bash
php artisan test tests/Unit/Services/AI/AIGradingServiceTest.php
```

### Feature Tests Only

```bash
php artisan test tests/Feature/AI/AIGradingTest.php
```

### Specific Test

```bash
php artisan test --filter=test_grades_essay_response_successfully
```

## Unit Tests

### AIGradingServiceTest

**Location**: `tests/Unit/Services/AI/AIGradingServiceTest.php`

#### Test Cases

1. **test_grades_essay_response_successfully**
   - Verifies AI grading works with valid input
   - Checks score calculation
   - Validates feedback generation
   - Confirms confidence scoring

2. **test_flags_low_confidence_responses_for_review**
   - Tests automatic flagging when confidence < 0.7
   - Verifies review reason is set
   - Checks flagged_for_review is true

3. **test_handles_ai_service_failure_gracefully**
   - Simulates AI service failure
   - Verifies graceful degradation
   - Checks response is flagged for manual review
   - Validates error logging

4. **test_throws_exception_for_non_essay_questions**
   - Ensures only essay questions can be AI graded
   - Validates exception is thrown for other types

5. **test_calculates_confidence_score_correctly**
   - Tests confidence calculation algorithm
   - Verifies adjustments for strengths/weaknesses
   - Checks clamping between 0 and 1

6. **test_calculates_confidence_with_many_weaknesses**
   - Tests confidence reduction with many weaknesses
   - Verifies -0.1 adjustment

7. **test_generates_appropriate_feedback**
   - Validates feedback structure
   - Checks inclusion of strengths, weaknesses, suggestions
   - Verifies appropriate tone based on score

8. **test_calculates_similarity_between_texts**
   - Tests plagiarism detection algorithm
   - Verifies similarity scoring
   - Checks edge cases (identical, completely different)

9. **test_batch_grades_multiple_essays**
   - Tests batch grading functionality
   - Verifies all responses are graded
   - Checks error handling in batch

10. **test_gets_flagged_responses**
    - Tests retrieval of flagged responses
    - Verifies filtering works correctly
    - Checks only unreviewed responses returned

11. **test_sets_low_confidence_threshold**
    - Tests threshold configuration
    - Verifies custom threshold is applied

### Running Unit Tests

```bash
# Run all unit tests
php artisan test tests/Unit/Services/AI/AIGradingServiceTest.php

# Run specific test
php artisan test tests/Unit/Services/AI/AIGradingServiceTest.php --filter=test_grades_essay_response_successfully

# Run with verbose output
php artisan test tests/Unit/Services/AI/AIGradingServiceTest.php -v
```

## Feature Tests

### AIGradingTest

**Location**: `tests/Feature/AI/AIGradingTest.php`

#### Test Cases

1. **test_essay_response_is_graded_by_ai_automatically**
   - Full workflow test: submit essay → AI grades → result stored
   - Verifies integration with AttemptService
   - Checks AIGradingResult is created

2. **test_low_confidence_grading_is_flagged_for_review**
   - Tests automatic flagging workflow
   - Verifies attempt status is "submitted" not "graded"
   - Checks review reason is set

3. **test_instructor_can_view_grading_review_queue**
   - Tests review queue page access
   - Verifies flagged responses are displayed
   - Checks filtering works

4. **test_instructor_can_override_ai_grading**
   - Tests override functionality
   - Verifies score and feedback update
   - Checks review tracking

5. **test_instructor_can_accept_ai_grading_without_changes**
   - Tests accept functionality
   - Verifies AI score is kept
   - Checks review is marked complete

6. **test_attempt_score_is_recalculated_after_override**
   - Tests score recalculation
   - Verifies total attempt score updates
   - Checks pass/fail status updates

7. **test_grading_statistics_are_accurate**
   - Tests statistics calculation
   - Verifies counts are correct
   - Checks average confidence calculation

### Running Feature Tests

```bash
# Run all feature tests
php artisan test tests/Feature/AI/AIGradingTest.php

# Run specific test
php artisan test tests/Feature/AI/AIGradingTest.php --filter=test_instructor_can_override_ai_grading

# Run with database refresh
php artisan test tests/Feature/AI/AIGradingTest.php --env=testing
```

## Manual Testing

### Setup Test Data

```bash
# Run seeders
php artisan db:seed

# Or create test data manually
php artisan tinker
```

```php
// In tinker
$user = User::factory()->create(['role' => 'instructor']);
$assessment = Assessment::factory()->create(['created_by' => $user->id]);
$question = Question::factory()->create([
    'assessment_id' => $assessment->id,
    'question_type' => 'essay',
    'points' => 10,
    'question_text' => 'Explain the concept of artificial intelligence.',
]);
```

### Test Scenarios

#### Scenario 1: High Confidence Grading

1. Create essay question
2. Student submits well-written response
3. Verify AI grades automatically
4. Check confidence score ≥ 0.7
5. Verify attempt status is "graded"
6. Check feedback is generated

**Expected Result**: Response is auto-graded, not flagged for review

#### Scenario 2: Low Confidence Grading

1. Create essay question
2. Student submits poor/unclear response
3. Verify AI grades but flags for review
4. Check confidence score < 0.7
5. Verify attempt status is "submitted"
6. Check response appears in review queue

**Expected Result**: Response is flagged for instructor review

#### Scenario 3: Instructor Override

1. Navigate to review queue
2. Select flagged response
3. Review AI grading and feedback
4. Enter new score and feedback
5. Submit override
6. Verify score updates
7. Check attempt total recalculates

**Expected Result**: AI score is overridden, attempt score updates

#### Scenario 4: Accept AI Grading

1. Navigate to review queue
2. Select flagged response
3. Review AI grading
4. Click "Accept" button
5. Verify response removed from queue
6. Check review is marked complete

**Expected Result**: AI grading accepted, response no longer flagged

#### Scenario 5: Batch Grading

1. Create assessment with multiple essay questions
2. Multiple students submit responses
3. Verify all are graded by AI
4. Check flagged responses in queue
5. Review statistics

**Expected Result**: All essays graded, appropriate responses flagged

### Manual Test Checklist

- [ ] Essay responses are graded automatically
- [ ] High confidence responses auto-grade
- [ ] Low confidence responses are flagged
- [ ] Review queue displays flagged responses
- [ ] Filters work (assessment, confidence)
- [ ] Review detail shows all information
- [ ] Override form validates input
- [ ] Override updates score correctly
- [ ] Accept button works
- [ ] Attempt score recalculates
- [ ] Statistics are accurate
- [ ] Feedback is displayed to students
- [ ] Rubric scores are shown
- [ ] Review history is tracked
- [ ] Error handling works

## Testing with Mock AI Service

### Setup Mock

```php
// In test
use App\Services\AI\Contracts\AIServiceInterface;
use Mockery;

$mockAI = Mockery::mock(AIServiceInterface::class);
$mockAI->shouldReceive('analyzeText')
    ->andReturn([
        'overall_score' => 85,
        'confidence' => 0.85,
        'strengths' => ['Clear', 'Detailed'],
        'weaknesses' => ['Minor issues'],
        'suggestions' => ['Add examples'],
        'rubric_scores' => ['content' => 8, 'grammar' => 9],
    ]);

$this->app->instance(AIServiceInterface::class, $mockAI);
```

### Test Different Scenarios

```php
// High confidence
$mockAI->shouldReceive('analyzeText')->andReturn([
    'overall_score' => 90,
    'confidence' => 0.9,
    // ...
]);

// Low confidence
$mockAI->shouldReceive('analyzeText')->andReturn([
    'overall_score' => 60,
    'confidence' => 0.5,
    // ...
]);

// AI failure
$mockAI->shouldReceive('analyzeText')
    ->andThrow(new AIServiceException('API error'));
```

## Performance Testing

### Test Large Batches

```php
// Create 100 essay responses
$responses = AttemptResponse::factory()->count(100)->create([
    'question_id' => $essayQuestion->id,
]);

// Time batch grading
$start = microtime(true);
$service->batchGradeEssays($responses->all());
$duration = microtime(true) - $start;

echo "Graded 100 essays in {$duration} seconds";
```

### Expected Performance

- Single essay: < 5 seconds
- Batch of 10: < 30 seconds
- Batch of 100: < 5 minutes

## Integration Testing

### Test with Real AI Service

```bash
# Set real API key
export OPENAI_API_KEY=your_real_key

# Run tests
php artisan test tests/Feature/AI/AIGradingTest.php --env=staging
```

**Note**: Use staging environment to avoid affecting production data

### Verify AI Responses

1. Submit various essay qualities
2. Check AI scores are reasonable
3. Verify feedback is helpful
4. Confirm confidence scores make sense
5. Test edge cases (very short, very long, off-topic)

## Debugging Tests

### Enable Verbose Output

```bash
php artisan test --filter=AIGrading -v
```

### Check Logs

```bash
tail -f storage/logs/laravel.log
```

### Use Tinker for Debugging

```bash
php artisan tinker
```

```php
// Test grading service directly
$service = app(\App\Services\AI\AIGradingService::class);
$response = \App\Models\AttemptResponse::find(1);
$result = $service->gradeEssay($response);
dd($result);
```

### Database Inspection

```bash
php artisan tinker
```

```php
// Check grading results
\App\Models\AIGradingResult::where('flagged_for_review', true)->count();

// Check confidence distribution
\App\Models\AIGradingResult::selectRaw('
    CASE 
        WHEN confidence_score >= 0.9 THEN "Very High"
        WHEN confidence_score >= 0.7 THEN "High"
        WHEN confidence_score >= 0.5 THEN "Medium"
        ELSE "Low"
    END as confidence_level,
    COUNT(*) as count
')->groupBy('confidence_level')->get();
```

## Common Issues

### Issue: All Tests Failing

**Solution**:
1. Check database connection
2. Run migrations: `php artisan migrate:fresh --env=testing`
3. Clear cache: `php artisan cache:clear`
4. Check AI service mock is set up

### Issue: Mock Not Working

**Solution**:
1. Verify mock is bound before test runs
2. Check mock expectations match actual calls
3. Use `Mockery::close()` in tearDown

### Issue: Database State Issues

**Solution**:
1. Use `RefreshDatabase` trait
2. Check factory definitions
3. Verify relationships are set up correctly

### Issue: Timeout Errors

**Solution**:
1. Increase timeout in phpunit.xml
2. Use mocks instead of real AI service
3. Reduce test data size

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test --filter=AIGrading
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: ":memory:"
```

## Test Coverage

### Generate Coverage Report

```bash
php artisan test --coverage --filter=AIGrading
```

### Expected Coverage

- AIGradingService: > 90%
- GradingReviewController: > 85%
- Integration: > 80%

## Best Practices

1. **Always use mocks** for AI service in tests
2. **Test edge cases** (empty responses, very long responses)
3. **Verify error handling** (AI failures, network issues)
4. **Check database state** after operations
5. **Test permissions** (only instructors can review)
6. **Validate input** (score ranges, required fields)
7. **Test concurrency** (multiple reviews at once)
8. **Monitor performance** (grading speed)

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Mockery Documentation](http://docs.mockery.io/)
- [AI Service Testing Guide](./AI_SERVICE_FOUNDATION_SUMMARY.md)
