# AI Content Analyzer - Testing Guide

## Test Files

### Unit Tests
**File**: `tests/Unit/Services/AI/AIContentAnalyzerServiceTest.php`

### Feature Tests
**File**: `tests/Feature/AI/ContentAnalyzerTest.php`

### Factory
**File**: `database/factories/ContentAnalysisFactory.php`

## Running Tests

### Run All Content Analyzer Tests
```bash
php artisan test --filter=ContentAnalyzer
```

### Run Unit Tests Only
```bash
php artisan test tests/Unit/Services/AI/AIContentAnalyzerServiceTest.php
```

### Run Feature Tests Only
```bash
php artisan test tests/Feature/AI/ContentAnalyzerTest.php
```

### Run Specific Test
```bash
php artisan test --filter=test_analyze_course_creates_content_analysis
```

## Unit Test Coverage

### Service Initialization
```php
public function test_analyze_course_creates_content_analysis()
```
- Verifies analysis is created
- Checks all required fields are populated
- Validates data structure

### Readability Analysis
```php
public function test_analyze_readability_returns_scores()
```
- Tests readability scoring
- Validates AI response parsing
- Checks score ranges

### Gap Identification
```php
public function test_identify_content_gaps_finds_missing_topics()
```
- Identifies missing topics
- Detects progression gaps
- Finds topics needing depth

### Accessibility Checks
```php
public function test_check_accessibility_identifies_video_without_transcript()
public function test_check_accessibility_identifies_long_lessons()
```
- Detects missing transcripts
- Identifies long content
- Validates severity levels

### Re-analysis
```php
public function test_re_analyze_course_marks_previous_as_not_current()
```
- Marks old analyses as not current
- Creates new analysis
- Maintains history

### Latest Analysis
```php
public function test_get_latest_analysis_returns_current_analysis()
```
- Returns current analysis only
- Ignores historical analyses
- Handles no analysis case

### Error Handling
```php
public function test_analyze_course_throws_exception_on_ai_failure()
```
- Handles AI service failures
- Throws appropriate exceptions
- Logs errors

### Score Calculation
```php
public function test_calculate_overall_score_considers_all_factors()
```
- Combines readability and engagement
- Applies gap penalties
- Applies accessibility penalties
- Ensures score bounds (0-100)

## Feature Test Coverage

### Access Control
```php
public function test_instructor_can_view_content_analyzer_page()
public function test_student_cannot_access_content_analyzer()
public function test_instructor_cannot_analyze_other_instructors_course()
public function test_admin_can_analyze_any_course()
```
- Verifies role-based access
- Tests course ownership
- Validates admin privileges

### Analysis Workflow
```php
public function test_instructor_can_trigger_content_analysis()
public function test_instructor_can_re_analyze_course()
```
- Tests analysis triggering
- Validates database updates
- Checks redirects and messages

### History Management
```php
public function test_instructor_can_view_analysis_history()
```
- Displays multiple analyses
- Shows chronological order
- Marks current analysis

### UI Display
```php
public function test_analysis_displays_readability_scores()
public function test_analysis_displays_content_gaps()
public function test_analysis_displays_suggestions()
public function test_analysis_displays_accessibility_issues()
```
- Verifies score display
- Shows gaps correctly
- Displays suggestions
- Shows accessibility issues

## Mock Setup

### AI Service Mock
```php
protected function setUp(): void
{
    parent::setUp();
    
    $mockAIService = Mockery::mock(AIServiceInterface::class);
    $mockAIService->shouldReceive('generateText')
        ->andReturn(
            json_encode(['readability_score' => 75, ...]),
            json_encode(['missing_topics' => [...], ...]),
            json_encode(['suggestions' => [...]])
        );
    
    $this->app->instance(AIServiceInterface::class, $mockAIService);
}
```

## Factory Usage in Tests

### Create Analysis
```php
$analysis = ContentAnalysis::factory()->create([
    'course_id' => $course->id,
]);
```

### High Score Analysis
```php
$analysis = ContentAnalysis::factory()
    ->withHighScore()
    ->create();
```

### Low Score Analysis
```php
$analysis = ContentAnalysis::factory()
    ->withLowScore()
    ->create();
```

### No Gaps
```php
$analysis = ContentAnalysis::factory()
    ->withNoGaps()
    ->create();
```

### Historical Analysis
```php
$oldAnalysis = ContentAnalysis::factory()
    ->notCurrent()
    ->create(['analyzed_at' => now()->subDays(7)]);
```

## Test Data Patterns

### Course with Content
```php
$course = Course::factory()->create();
$module = CourseModule::factory()->create(['course_id' => $course->id]);
$lesson = CourseLesson::factory()->create([
    'module_id' => $module->id,
    'content_type' => 'text',
    'content' => 'Test content',
]);
```

### Course with Video
```php
$lesson = CourseLesson::factory()->create([
    'module_id' => $module->id,
    'content_type' => 'video',
    'duration' => 45, // Long video
]);
```

### Multiple Analyses
```php
ContentAnalysis::factory()->count(3)->create([
    'course_id' => $course->id,
    'is_current' => false,
]);

ContentAnalysis::factory()->create([
    'course_id' => $course->id,
    'is_current' => true,
]);
```

## Assertion Examples

### Database Assertions
```php
$this->assertDatabaseHas('content_analysis', [
    'course_id' => $course->id,
    'is_current' => true,
]);

$this->assertDatabaseCount('content_analysis', 2);
```

### Model Assertions
```php
$this->assertInstanceOf(ContentAnalysis::class, $analysis);
$this->assertEquals($course->id, $analysis->course_id);
$this->assertNotNull($analysis->overall_score);
$this->assertTrue($analysis->is_current);
```

### Score Assertions
```php
$this->assertGreaterThan(0, $analysis->overall_score);
$this->assertLessThanOrEqual(100, $analysis->overall_score);
$this->assertEquals('B', $analysis->getScoreGrade());
```

### Collection Assertions
```php
$this->assertNotEmpty($analysis->content_gaps);
$this->assertArrayHasKey('missing_topics', $analysis->content_gaps);
$this->assertCount(2, $analysis->suggestions);
```

### Response Assertions
```php
$response->assertStatus(200);
$response->assertViewIs('admin.content-analyzer.show');
$response->assertViewHas('course', $course);
$response->assertSee('85.5'); // Score display
$response->assertSee('Advanced concepts'); // Gap display
```

## Edge Cases to Test

### Empty Course
```php
public function test_analyze_empty_course()
{
    $course = Course::factory()->create();
    // No modules or lessons
    
    $analysis = $this->service->analyzeCourse($course);
    
    $this->assertNotNull($analysis);
}
```

### Course Without Objectives
```php
public function test_analyze_course_without_objectives()
{
    $course = Course::factory()->create([
        'learning_objectives' => null,
    ]);
    
    $analysis = $this->service->analyzeCourse($course);
    
    $this->assertNotNull($analysis);
}
```

### Multiple Re-analyses
```php
public function test_multiple_re_analyses()
{
    $course = Course::factory()->create();
    
    $analysis1 = $this->service->analyzeCourse($course);
    $analysis2 = $this->service->reAnalyzeCourse($course);
    $analysis3 = $this->service->reAnalyzeCourse($course);
    
    $this->assertFalse($analysis1->fresh()->is_current);
    $this->assertFalse($analysis2->fresh()->is_current);
    $this->assertTrue($analysis3->fresh()->is_current);
}
```

## Performance Testing

### Large Course Analysis
```php
public function test_analyze_large_course()
{
    $course = Course::factory()->create();
    $module = CourseModule::factory()->create(['course_id' => $course->id]);
    
    // Create 50 lessons
    CourseLesson::factory()->count(50)->create([
        'module_id' => $module->id,
    ]);
    
    $startTime = microtime(true);
    $analysis = $this->service->analyzeCourse($course);
    $duration = microtime(true) - $startTime;
    
    $this->assertLessThan(30, $duration); // Should complete in 30 seconds
}
```

## Integration Testing

### Full Workflow Test
```php
public function test_complete_analysis_workflow()
{
    $instructor = User::factory()->create(['role' => 'instructor']);
    $course = Course::factory()->create(['created_by' => $instructor->id]);
    $module = CourseModule::factory()->create(['course_id' => $course->id]);
    CourseLesson::factory()->create(['module_id' => $module->id]);
    
    // Trigger analysis
    $response = $this->actingAs($instructor)
        ->post(route('admin.content-analyzer.analyze', $course));
    
    $response->assertRedirect();
    
    // View results
    $response = $this->actingAs($instructor)
        ->get(route('admin.content-analyzer.show', $course));
    
    $response->assertStatus(200);
    $response->assertViewHas('analysis');
    
    // Re-analyze
    $response = $this->actingAs($instructor)
        ->post(route('admin.content-analyzer.re-analyze', $course));
    
    $response->assertRedirect();
    
    // View history
    $response = $this->actingAs($instructor)
        ->get(route('admin.content-analyzer.history', $course));
    
    $response->assertStatus(200);
    $this->assertEquals(2, ContentAnalysis::where('course_id', $course->id)->count());
}
```

## Continuous Integration

### GitHub Actions Example
```yaml
- name: Run Content Analyzer Tests
  run: |
    php artisan test --filter=ContentAnalyzer --coverage-html coverage
```

## Test Maintenance

### Update Tests When:
1. Adding new analysis metrics
2. Changing score calculation
3. Adding new accessibility checks
4. Modifying AI prompts
5. Updating UI components

### Best Practices:
1. Mock AI service in all tests
2. Use factories for test data
3. Test both success and failure cases
4. Verify authorization at all levels
5. Test UI display of all data types
6. Include edge cases
7. Test performance with large datasets

## Debugging Tests

### Enable Logging
```php
Log::info('Analysis result', ['analysis' => $analysis->toArray()]);
```

### Dump Variables
```php
dump($analysis->content_gaps);
dd($analysis->suggestions);
```

### Check Database State
```php
$this->assertDatabaseHas('content_analysis', [
    'course_id' => $course->id,
]);

DB::table('content_analysis')->get()->dd();
```

## Coverage Goals

- **Unit Tests**: 90%+ coverage of service methods
- **Feature Tests**: 80%+ coverage of controller actions
- **Integration Tests**: All user workflows covered
- **Edge Cases**: All error conditions tested

## Related Documentation
- [AI Content Analyzer Summary](AI_CONTENT_ANALYZER_SUMMARY.md)
- [AI Content Analyzer Quick Reference](AI_CONTENT_ANALYZER_QUICK_REFERENCE.md)
- [AI Service Testing](AI_SERVICE_FOUNDATION_SUMMARY.md)
