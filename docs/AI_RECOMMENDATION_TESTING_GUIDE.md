# AI Recommendation Engine - Testing Guide

## Running Tests

### Run All Recommendation Tests
```bash
php artisan test --filter=Recommendation
```

### Run Feature Tests Only
```bash
php artisan test tests/Feature/AI/RecommendationTest.php
```

### Run Unit Tests Only
```bash
php artisan test tests/Unit/Services/AI/AIRecommendationServiceTest.php
```

### Run Specific Test
```bash
php artisan test --filter=it_generates_recommendations_for_user
```

## Manual Testing Scenarios

### Scenario 1: First-Time User Recommendations

**Setup:**
1. Create a new user account
2. Don't enroll in any courses yet

**Steps:**
1. Login as the new user
2. Navigate to dashboard
3. Click "Generate Recommendations" button
4. Wait for processing (or check queue)

**Expected Results:**
- System generates 5 recommendations
- Recommendations are for beginner-level courses
- Each recommendation has reasoning
- Match scores are displayed
- Recommendations appear on dashboard

### Scenario 2: Experienced User Recommendations

**Setup:**
1. Create user with completed courses
2. Add enrollments with "completed" status
3. Add assessment attempts with high scores

**Steps:**
1. Login as the user
2. Navigate to `/recommendations`
3. Click "Generate New Recommendations"

**Expected Results:**
- Recommendations match user's skill level
- Suggested courses are intermediate/advanced
- Reasoning references completed courses
- Higher match scores for relevant courses

### Scenario 3: Accept Recommendation

**Setup:**
1. User with active recommendations

**Steps:**
1. Navigate to recommendations page
2. Click "Accept & Enroll" on a recommendation
3. Complete enrollment process

**Expected Results:**
- Recommendation status changes to "accepted"
- User is redirected to enrollment page
- Feedback is recorded
- Recommendation no longer shows as active

### Scenario 4: Reject Recommendation

**Setup:**
1. User with active recommendations

**Steps:**
1. Navigate to recommendations page
2. Click "Dismiss" on a recommendation
3. Optionally provide feedback

**Expected Results:**
- Recommendation status changes to "rejected"
- Feedback is recorded
- Recommendation is removed from active list
- Success message displayed

### Scenario 5: Expired Recommendations

**Setup:**
1. Create recommendations with past expiration dates

**Steps:**
1. Navigate to recommendations page
2. Attempt to view expired recommendations

**Expected Results:**
- Expired recommendations don't appear
- Only active, non-expired recommendations shown
- User can generate new recommendations

### Scenario 6: Scheduled Generation

**Setup:**
1. Configure schedule to run immediately
2. Have multiple users needing recommendations

**Steps:**
1. Run: `php artisan recommendations:generate`
2. Monitor queue processing
3. Check database for new recommendations

**Expected Results:**
- Job queued for each user
- Recommendations generated asynchronously
- Progress bar shows completion
- Logs show successful generation

## Testing with Mock AI Service

### Example Test with Mocked Response
```php
public function test_recommendation_generation_with_mock()
{
    // Arrange
    $user = User::factory()->create();
    $course = Course::factory()->create(['is_published' => true]);
    
    $mockResponse = json_encode([
        'recommendations' => [
            [
                'course_id' => $course->id,
                'score' => 0.95,
                'reasoning' => 'Perfect match for your skills.'
            ]
        ]
    ]);
    
    $mockAI = $this->mock(AIServiceInterface::class);
    $mockAI->shouldReceive('generateText')
        ->once()
        ->with(\Mockery::type('string'), \Mockery::type('array'))
        ->andReturn($mockResponse);
    
    $service = new AIRecommendationService($mockAI);
    
    // Act
    $recommendations = $service->generateRecommendations($user, 5);
    
    // Assert
    $this->assertCount(1, $recommendations);
    $this->assertEquals($course->id, $recommendations[0]['course_id']);
    $this->assertEquals(0.95, $recommendations[0]['score']);
}
```

## API Testing with Postman/Insomnia

### Generate Recommendations
```http
POST /recommendations/generate
Authorization: Bearer {token}
Content-Type: application/json
```

**Expected Response:**
```json
{
    "success": true,
    "message": "New recommendations generated successfully! Found 5 courses for you."
}
```

### Accept Recommendation
```http
POST /recommendations/{id}/accept
Authorization: Bearer {token}
Content-Type: application/json

{
    "feedback": "This looks great!",
    "enroll": "true"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Recommendation accepted! Proceeding to enrollment."
}
```

### Reject Recommendation
```http
POST /recommendations/{id}/reject
Authorization: Bearer {token}
Content-Type: application/json

{
    "feedback": "Not relevant to my role."
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Recommendation dismissed. We'll improve future recommendations based on your feedback."
}
```

## Database Testing

### Check Recommendation Storage
```sql
-- View all recommendations for a user
SELECT * FROM ai_recommendations 
WHERE user_id = 1 
ORDER BY score DESC;

-- Check active recommendations
SELECT * FROM ai_recommendations 
WHERE user_id = 1 
AND status = 'active'
AND (expires_at IS NULL OR expires_at > NOW());

-- View acceptance rate
SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
FROM ai_recommendations
GROUP BY status;

-- Average recommendation score
SELECT AVG(score) as avg_score
FROM ai_recommendations
WHERE status = 'active';
```

## Performance Testing

### Load Test Recommendation Generation
```php
// Generate recommendations for 100 users
$users = User::limit(100)->get();

$startTime = microtime(true);

foreach ($users as $user) {
    GenerateRecommendationsJob::dispatch($user);
}

$endTime = microtime(true);
$duration = $endTime - $startTime;

echo "Queued 100 jobs in {$duration} seconds\n";
```

### Monitor Queue Processing
```bash
# Watch queue processing
php artisan queue:work --verbose

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Edge Cases to Test

### 1. User with No Available Courses
**Setup:** User enrolled in all published courses

**Expected:** Empty recommendations or message indicating no new courses

### 2. User with No Learning History
**Setup:** New user with no enrollments

**Expected:** Beginner-level recommendations based on profile only

### 3. AI Service Failure
**Setup:** Mock AI service to throw exception

**Expected:** Error logged, job retries, user notified of failure

### 4. Invalid AI Response
**Setup:** Mock AI service to return invalid JSON

**Expected:** Empty recommendations, error logged, graceful handling

### 5. Concurrent Recommendation Generation
**Setup:** Multiple requests to generate recommendations simultaneously

**Expected:** Only one set of recommendations created, old ones expired

### 6. Expired Recommendations
**Setup:** Recommendations with past expiration dates

**Expected:** Not shown in active list, can be filtered out

### 7. Unauthorized Access
**Setup:** User A tries to accept User B's recommendation

**Expected:** 403 Forbidden error

## Debugging Tips

### Enable Query Logging
```php
DB::enableQueryLog();

// Your code here

dd(DB::getQueryLog());
```

### Log AI Prompts
```php
// In AIRecommendationService
Log::info('AI Prompt', ['prompt' => $prompt]);
```

### Check Queue Jobs
```bash
# View queued jobs
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# View job details
php artisan queue:failed {id}
```

### Monitor AI Service Calls
```php
// In OpenAIService
Log::info('AI Request', [
    'operation' => 'recommendation',
    'user_id' => $user->id,
    'prompt_length' => strlen($prompt)
]);
```

## Test Data Seeding

### Seed Test Recommendations
```php
use App\Models\User;
use App\Models\Course;
use App\Models\AIRecommendation;

// Create users with recommendations
User::factory()
    ->count(10)
    ->has(
        AIRecommendation::factory()
            ->count(5)
            ->for(Course::factory()->state(['is_published' => true]))
    )
    ->create();
```

### Seed Diverse Scenarios
```php
// User with accepted recommendations
$user1 = User::factory()->create();
AIRecommendation::factory()
    ->count(3)
    ->accepted()
    ->create(['user_id' => $user1->id]);

// User with rejected recommendations
$user2 = User::factory()->create();
AIRecommendation::factory()
    ->count(2)
    ->rejected()
    ->create(['user_id' => $user2->id]);

// User with expired recommendations
$user3 = User::factory()->create();
AIRecommendation::factory()
    ->count(4)
    ->expired()
    ->create(['user_id' => $user3->id]);
```

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
          php-version: '8.2'
          
      - name: Install Dependencies
        run: composer install
        
      - name: Run Tests
        run: php artisan test --filter=Recommendation
```

## Coverage Report

### Generate Coverage Report
```bash
php artisan test --coverage --filter=Recommendation
```

### Expected Coverage
- Service methods: 90%+
- Controller actions: 85%+
- Model methods: 95%+
- Overall: 85%+

## Checklist

### Before Deployment
- [ ] All tests passing
- [ ] No diagnostics errors
- [ ] Database migrations run
- [ ] Queue workers configured
- [ ] Scheduled tasks configured
- [ ] AI service credentials set
- [ ] Error logging enabled
- [ ] Rate limiting configured

### After Deployment
- [ ] Generate test recommendations
- [ ] Verify dashboard widget
- [ ] Test accept/reject actions
- [ ] Check queue processing
- [ ] Monitor AI service costs
- [ ] Review error logs
- [ ] Verify scheduled tasks
- [ ] Test with real users

## Common Issues and Solutions

### Issue: Recommendations Not Generating
**Solution:** Check AI service configuration and API keys

### Issue: Queue Jobs Failing
**Solution:** Ensure queue worker is running and database is accessible

### Issue: Slow Generation
**Solution:** Optimize AI prompt, reduce available courses limit

### Issue: Low Match Scores
**Solution:** Review user profile analysis and skill gap identification

### Issue: No Recommendations for User
**Solution:** Verify published courses exist and user isn't enrolled in all

## Metrics to Track

1. **Generation Success Rate**
   - Target: >95%
   
2. **Average Generation Time**
   - Target: <30 seconds
   
3. **Acceptance Rate**
   - Target: >30%
   
4. **User Engagement**
   - Target: >50% of users view recommendations
   
5. **AI Service Costs**
   - Monitor per-user cost
   
6. **Queue Processing Time**
   - Target: <5 minutes for batch jobs
