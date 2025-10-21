# AI Content Analyzer - Quick Reference

## Quick Start

### Analyze Course Content
```php
use App\Services\AI\AIContentAnalyzerService;

$analyzer = app(AIContentAnalyzerService::class);
$analysis = $analyzer->analyzeCourse($course);
```

### Access from UI
1. Go to Admin → Courses → [Course Name]
2. Click "Analyze Content" button
3. View analysis results

## Key Routes

```php
// View analysis
GET /admin/courses/{course}/analyze

// Trigger analysis
POST /admin/courses/{course}/analyze

// Re-analyze
POST /admin/courses/{course}/re-analyze

// View history
GET /admin/courses/{course}/analyze/history
```

## Analysis Components

### Overall Score
- Range: 0-100
- Grade: A (90+), B (80-89), C (70-79), D (60-69), F (<60)
- Factors: Readability, engagement, gaps, accessibility

### Readability Score
- Measures content clarity
- Evaluates sentence structure
- Assesses vocabulary complexity

### Engagement Score
- Evaluates content interactivity
- Measures learning potential
- Assesses content appeal

### Content Gaps
- Missing topics
- Progression gaps
- Topics needing depth
- Missing prerequisites

### Suggestions
- Prioritized (high/medium/low)
- Categorized (readability/structure/engagement/completeness)
- Actionable recommendations
- Expected impact

### Accessibility Issues
- Missing transcripts
- Long content (>30 min)
- WCAG compliance issues
- Severity levels

## Service Methods

```php
// Analyze course
$analysis = $analyzer->analyzeCourse($course);

// Re-analyze (marks previous as not current)
$analysis = $analyzer->reAnalyzeCourse($course);

// Get latest analysis
$latest = $analyzer->getLatestAnalysis($course);
```

## Model Methods

```php
// Get overall score
$score = $analysis->getOverallScore();

// Get letter grade
$grade = $analysis->getScoreGrade(); // A, B, C, D, F

// Check for gaps
$hasGaps = $analysis->hasGaps();

// Check for accessibility issues
$hasIssues = $analysis->hasAccessibilityIssues();

// Get total issues count
$count = $analysis->getTotalIssues();
```

## View Components

### Score Display
```blade
<div class="text-6xl font-bold">
    {{ number_format($analysis->overall_score, 1) }}
</div>
<div class="text-3xl font-bold">
    {{ $analysis->getScoreGrade() }}
</div>
```

### Progress Bar
```blade
<div class="w-full bg-gray-200 rounded-full h-4">
    <div class="bg-blue-600 h-4 rounded-full" 
         style="width: {{ $analysis->overall_score }}%">
    </div>
</div>
```

### Content Gaps
```blade
@if($analysis->hasGaps())
    @foreach($analysis->content_gaps['missing_topics'] as $topic)
        <li>{{ $topic }}</li>
    @endforeach
@endif
```

### Suggestions
```blade
@foreach($analysis->suggestions as $suggestion)
    <div class="border-l-4 {{ $suggestion['priority'] === 'high' ? 'border-red-500' : 'border-blue-500' }}">
        <span class="font-semibold">{{ $suggestion['suggestion'] }}</span>
        <p class="text-sm">Impact: {{ $suggestion['impact'] }}</p>
    </div>
@endforeach
```

## Testing

### Unit Test Example
```php
public function test_analyze_course_creates_analysis()
{
    $course = Course::factory()->create();
    $analysis = $this->service->analyzeCourse($course);
    
    $this->assertInstanceOf(ContentAnalysis::class, $analysis);
    $this->assertNotNull($analysis->overall_score);
}
```

### Feature Test Example
```php
public function test_instructor_can_trigger_analysis()
{
    $instructor = User::factory()->create(['role' => 'instructor']);
    $course = Course::factory()->create(['created_by' => $instructor->id]);
    
    $response = $this->actingAs($instructor)
        ->post(route('admin.content-analyzer.analyze', $course));
    
    $response->assertRedirect();
    $this->assertDatabaseHas('content_analysis', ['course_id' => $course->id]);
}
```

## Factory Usage

```php
// Create analysis with high score
$analysis = ContentAnalysis::factory()
    ->withHighScore()
    ->create();

// Create analysis with no gaps
$analysis = ContentAnalysis::factory()
    ->withNoGaps()
    ->create();

// Create historical analysis
$analysis = ContentAnalysis::factory()
    ->notCurrent()
    ->create();
```

## Common Patterns

### Check if Analysis Exists
```php
$analysis = $analyzer->getLatestAnalysis($course);
if ($analysis) {
    // Display results
} else {
    // Show "Analyze" button
}
```

### Display Score with Color
```php
$scoreClass = match($analysis->getScoreGrade()) {
    'A' => 'text-green-600',
    'B' => 'text-blue-600',
    'C' => 'text-yellow-600',
    default => 'text-red-600',
};
```

### Filter by Priority
```php
$highPriority = collect($analysis->suggestions)
    ->where('priority', 'high')
    ->all();
```

## Authorization

```php
// Check if user can analyze
$this->authorize('update', $course);

// In policy
public function update(User $user, Course $course)
{
    return $user->id === $course->created_by 
        || $user->role === 'admin';
}
```

## Error Handling

```php
try {
    $analysis = $analyzer->analyzeCourse($course);
} catch (AIServiceException $e) {
    Log::error('Analysis failed', ['error' => $e->getMessage()]);
    return back()->with('error', 'Failed to analyze content');
}
```

## Performance Tips

1. **Cache Results**: Analysis results are stored in database
2. **Async Processing**: Queue analysis for large courses
3. **Rate Limiting**: AI service calls are rate-limited
4. **Pagination**: Use pagination for analysis history

## Troubleshooting

### Analysis Not Showing
- Check if course has content (modules/lessons)
- Verify AI service is configured
- Check logs for errors

### Low Scores
- Review content gaps
- Check accessibility issues
- Follow improvement suggestions

### Permission Denied
- Verify user role (instructor/admin)
- Check course ownership
- Review policy rules

## Best Practices

1. **Regular Analysis**: Re-analyze after major content updates
2. **Track History**: Review trends over time
3. **Act on Suggestions**: Implement high-priority recommendations
4. **Fix Accessibility**: Address accessibility issues promptly
5. **Monitor Scores**: Aim for B grade or higher

## Related Features

- Course Management
- Content Delivery
- AI Services
- Learning Analytics
