# AI Recommendation Engine - Quick Reference

## Service Usage

### Generate Recommendations
```php
use App\Services\AI\AIRecommendationService;

$service = app(AIRecommendationService::class);

// Generate 5 recommendations for a user
$recommendations = $service->generateRecommendations($user, 5);

// Get active recommendations
$active = $service->getActiveRecommendations($user);

// Get personalized courses
$courses = $service->getPersonalizedCourses($user, 10);

// Check if user needs new recommendations
if ($service->needsNewRecommendations($user)) {
    // Generate new recommendations
}

// Record feedback
$service->recordFeedback($recommendation, 'accept', 'Very helpful!');
$service->recordFeedback($recommendation, 'reject', 'Not relevant.');
```

## Controller Routes

### User Routes
```php
// View all recommendations
GET /recommendations

// Generate new recommendations
POST /recommendations/generate

// Accept recommendation
POST /recommendations/{recommendation}/accept
// Optional: Add ?enroll=true to auto-enroll

// Reject recommendation
POST /recommendations/{recommendation}/reject

// Record feedback (AJAX)
POST /recommendations/{recommendation}/feedback
```

## Background Jobs

### Queue Recommendation Generation
```php
use App\Jobs\GenerateRecommendationsJob;

// Dispatch job
GenerateRecommendationsJob::dispatch($user, 5);

// Dispatch with delay
GenerateRecommendationsJob::dispatch($user, 5)
    ->delay(now()->addMinutes(5));
```

### CLI Commands
```bash
# Generate for all users needing recommendations
php artisan recommendations:generate

# Generate for specific user
php artisan recommendations:generate --user-id=123

# Force regeneration for all users
php artisan recommendations:generate --force

# Custom limit per user
php artisan recommendations:generate --limit=10
```

## Model Usage

### Query Recommendations
```php
use App\Models\AIRecommendation;

// Get active recommendations for user
$recommendations = AIRecommendation::where('user_id', $user->id)
    ->active()
    ->with('course.category')
    ->orderBy('score', 'desc')
    ->get();

// Get accepted recommendations
$accepted = AIRecommendation::where('user_id', $user->id)
    ->accepted()
    ->get();

// Get rejected recommendations
$rejected = AIRecommendation::where('user_id', $user->id)
    ->rejected()
    ->get();
```

### Recommendation Actions
```php
// Accept recommendation
$recommendation->accept('This looks great!');

// Reject recommendation
$recommendation->reject('Not interested.');

// Check status
if ($recommendation->isActive()) {
    // Recommendation is active
}

if ($recommendation->isExpired()) {
    // Recommendation has expired
}
```

## View Integration

### Dashboard Widget
```blade
@php
    $recommendations = Auth::user()->recommendations()
        ->active()
        ->with('course.category')
        ->orderBy('score', 'desc')
        ->limit(3)
        ->get();
    $needsNew = $recommendations->count() < 3;
@endphp

@include('recommendations.dashboard-widget', [
    'recommendations' => $recommendations,
    'needsNew' => $needsNew
])
```

### Full Page
```blade
<a href="{{ route('recommendations.index') }}">
    View All Recommendations
</a>
```

## Testing

### Feature Tests
```php
use Tests\TestCase;
use App\Models\User;
use App\Models\AIRecommendation;

class RecommendationTest extends TestCase
{
    /** @test */
    public function user_can_accept_recommendation()
    {
        $user = User::factory()->create();
        $recommendation = AIRecommendation::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('recommendations.accept', $recommendation))
            ->assertRedirect();

        $recommendation->refresh();
        $this->assertEquals('accepted', $recommendation->status);
    }
}
```

### Unit Tests with Mocked AI
```php
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\AIRecommendationService;

$mockAIService = $this->mock(AIServiceInterface::class);
$mockAIService->shouldReceive('generateText')
    ->once()
    ->andReturn(json_encode([
        'recommendations' => [
            [
                'course_id' => 1,
                'score' => 0.95,
                'reasoning' => 'Great match!'
            ]
        ]
    ]));

$service = new AIRecommendationService($mockAIService);
$recommendations = $service->generateRecommendations($user);
```

## Factory Usage

### Create Test Recommendations
```php
use App\Models\AIRecommendation;

// Basic recommendation
$recommendation = AIRecommendation::factory()->create();

// Accepted recommendation
$accepted = AIRecommendation::factory()->accepted()->create();

// Rejected recommendation
$rejected = AIRecommendation::factory()->rejected()->create();

// Expired recommendation
$expired = AIRecommendation::factory()->expired()->create();

// High score recommendation
$highScore = AIRecommendation::factory()->highScore()->create();

// Multiple recommendations for user
$recommendations = AIRecommendation::factory()
    ->count(5)
    ->create(['user_id' => $user->id]);
```

## Scheduled Tasks

### Configure Schedule
In `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

// Weekly on Mondays at 2 AM
Schedule::command('recommendations:generate')
    ->weekly()
    ->mondays()
    ->at('02:00');

// Daily at midnight
Schedule::command('recommendations:generate')
    ->daily()
    ->at('00:00');

// Every hour
Schedule::command('recommendations:generate')
    ->hourly();
```

## Common Patterns

### Generate on User Registration
```php
use App\Jobs\GenerateRecommendationsJob;

// In RegisterController or event listener
GenerateRecommendationsJob::dispatch($user, 5)
    ->delay(now()->addMinutes(5));
```

### Regenerate After Course Completion
```php
// In course completion event listener
if ($service->needsNewRecommendations($user)) {
    GenerateRecommendationsJob::dispatch($user);
}
```

### Display Recommendation Count
```php
$activeCount = $user->recommendations()
    ->active()
    ->count();

@if($activeCount > 0)
    <span class="badge">{{ $activeCount }} new recommendations</span>
@endif
```

## Troubleshooting

### No Recommendations Generated
1. Check AI service configuration
2. Verify user has learning history
3. Check available published courses
4. Review logs for AI service errors

### Recommendations Not Showing
1. Verify recommendations are active
2. Check expiration dates
3. Ensure courses are published
4. Check user authorization

### Job Failures
1. Check queue worker is running
2. Review failed jobs table
3. Check AI service rate limits
4. Verify database connections

## Performance Tips

1. **Eager Load Relationships**
   ```php
   $recommendations = AIRecommendation::with('course.category')
       ->where('user_id', $user->id)
       ->get();
   ```

2. **Use Queues for Generation**
   ```php
   // Don't generate synchronously in web requests
   GenerateRecommendationsJob::dispatch($user);
   ```

3. **Limit Query Results**
   ```php
   $recommendations = $user->recommendations()
       ->active()
       ->limit(10)
       ->get();
   ```

4. **Index Database Columns**
   - `user_id`
   - `status`
   - `expires_at`
   - `score`

## Security Checklist

- ✅ Authorization checks in controller
- ✅ User can only access own recommendations
- ✅ Input validation on feedback
- ✅ SQL injection prevention (Eloquent)
- ✅ XSS prevention (Blade escaping)
- ✅ CSRF protection on forms

## Monitoring Queries

### Recommendation Statistics
```php
// Total recommendations generated
$total = AIRecommendation::count();

// Acceptance rate
$accepted = AIRecommendation::accepted()->count();
$rate = ($accepted / $total) * 100;

// Average score
$avgScore = AIRecommendation::avg('score');

// Recommendations by status
$byStatus = AIRecommendation::groupBy('status')
    ->selectRaw('status, count(*) as count')
    ->get();
```

### User Engagement
```php
// Users with active recommendations
$usersWithRecs = User::whereHas('recommendations', function($q) {
    $q->active();
})->count();

// Most recommended courses
$topCourses = AIRecommendation::select('course_id')
    ->selectRaw('count(*) as recommendation_count')
    ->groupBy('course_id')
    ->orderByDesc('recommendation_count')
    ->limit(10)
    ->with('course')
    ->get();
```
