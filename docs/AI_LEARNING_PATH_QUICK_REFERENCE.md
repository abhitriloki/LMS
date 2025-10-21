# AI Learning Path Optimizer - Quick Reference

## Routes

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| GET | `/learning-paths` | index | List user's learning paths |
| GET | `/learning-paths/create` | create | Show creation form |
| POST | `/learning-paths` | store | Generate new learning path |
| GET | `/learning-paths/{id}` | show | View learning path details |
| POST | `/learning-paths/{id}/start` | start | Start a learning path |
| POST | `/learning-paths/{id}/pause` | pause | Pause a learning path |
| POST | `/learning-paths/{id}/resume` | resume | Resume a learning path |
| POST | `/learning-paths/{id}/optimize` | optimize | Optimize learning path |
| POST | `/learning-paths/{id}/adjust` | adjust | Adjust based on performance |
| POST | `/learning-paths/{id}/update-progress` | updateProgress | Refresh progress |
| DELETE | `/learning-paths/{id}` | destroy | Delete learning path |

## Service Methods

### AILearningPathService

```php
// Generate new learning path
$learningPath = $service->generateLearningPath($user, 'Senior Developer');

// Optimize existing path
$optimizedPath = $service->optimizePath($learningPath);

// Adjust path based on performance
$performanceData = [
    'average_score' => 85,
    'completion_rate' => 90,
    'time_spent' => 100,
    'estimated_time' => 120,
];
$adjustedPath = $service->adjustPath($learningPath, $performanceData);

// Update progress
$service->updateProgress($learningPath);

// Get next course
$nextCourse = $service->getNextCourse($learningPath);
```

## Model Methods

### AILearningPath

```php
// Status checks
$learningPath->isActive();
$learningPath->isCompleted();
$learningPath->isPaused();

// Lifecycle management
$learningPath->start();
$learningPath->pause();
$learningPath->resume();
$learningPath->complete();

// Progress management
$learningPath->updateProgress(50.0);

// Adjustments
$learningPath->adjust($newPathData, 'Performance improvement');
```

## Path Data Structure

```php
[
    'reasoning' => 'AI explanation of path design',
    'courses' => [
        [
            'course_id' => 1,
            'order' => 1,
            'milestone' => 'Foundation',
            'reason' => 'Why this course is included',
            'estimated_weeks' => 2,
        ],
        // ... more courses
    ],
    'milestones' => [
        [
            'name' => 'Foundation Complete',
            'course_ids' => [1, 2],
            'description' => 'Basic skills acquired',
        ],
        // ... more milestones
    ],
    'total_estimated_weeks' => 12,
    'difficulty_progression' => 'beginner -> intermediate -> advanced',
]
```

## Status Values

- `draft` - Path generated but not started
- `active` - User is actively following the path
- `paused` - Path temporarily paused
- `completed` - All courses completed

## Authorization

### Policies

```php
// Users can view their own paths
$this->authorize('view', $learningPath);

// Users can update their own paths
$this->authorize('update', $learningPath);

// Users can delete their own paths
$this->authorize('delete', $learningPath);

// Admins can view all paths
if ($user->role === 'super_admin' || $user->role === 'hr_admin') {
    // Can view any path
}
```

## Common Workflows

### Create Learning Path

```php
// In controller
public function store(Request $request)
{
    $validated = $request->validate([
        'target_role' => 'required|string|max:255',
    ]);

    $learningPath = $this->learningPathService->generateLearningPath(
        Auth::user(),
        $validated['target_role']
    );

    return redirect()->route('learning-paths.show', $learningPath);
}
```

### Display Learning Path

```php
// In controller
public function show(AILearningPath $learningPath)
{
    $this->authorize('view', $learningPath);
    
    $nextCourse = $this->learningPathService->getNextCourse($learningPath);
    
    $pathCourses = collect($learningPath->path_data['courses'] ?? []);
    $courseIds = $pathCourses->pluck('course_id')->toArray();
    
    $courses = Course::whereIn('id', $courseIds)->get()->keyBy('id');
    
    return view('learning-paths.show', compact('learningPath', 'courses', 'nextCourse'));
}
```

### Update Progress

```php
// Manually trigger progress update
$learningPathService->updateProgress($learningPath);

// Or via AJAX
fetch('/learning-paths/' + pathId + '/update-progress', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
    }
})
.then(response => response.json())
.then(data => {
    console.log('Progress:', data.progress);
});
```

## View Components

### Learning Path Card (Index)

```blade
<div class="learning-path-card">
    <h3>{{ $path->target_role }}</h3>
    <span class="status-badge">{{ ucfirst($path->status) }}</span>
    
    <div class="progress-bar">
        <div style="width: {{ $path->progress_percentage }}%"></div>
    </div>
    
    <div class="meta">
        <span>{{ count($path->path_data['courses']) }} courses</span>
        <span>{{ $path->estimated_duration }} weeks</span>
    </div>
</div>
```

### Course Step (Show)

```blade
<div class="course-step @if($isNext) next @elseif($isCompleted) completed @endif">
    <div class="step-number">{{ $order }}</div>
    
    <div class="course-info">
        <h4>{{ $course->title }}</h4>
        <p>{{ $pathCourse['reason'] }}</p>
        
        @if($isNext)
            <a href="{{ route('courses.show', $course) }}">Start Course</a>
        @elseif($isCompleted)
            <span>✓ Completed</span>
        @else
            <span>🔒 Locked</span>
        @endif
    </div>
</div>
```

## Testing

### Unit Test Example

```php
public function test_generates_learning_path()
{
    $user = User::factory()->create();
    $course = Course::factory()->create(['is_published' => true]);
    
    $aiService = Mockery::mock(AIServiceInterface::class);
    $aiService->shouldReceive('generateText')
        ->once()
        ->andReturn(json_encode([
            'reasoning' => 'Test',
            'courses' => [
                ['course_id' => $course->id, 'order' => 1, 'estimated_weeks' => 4]
            ],
            'milestones' => [],
        ]));
    
    $service = new AILearningPathService($aiService);
    $path = $service->generateLearningPath($user, 'Senior Developer');
    
    $this->assertInstanceOf(AILearningPath::class, $path);
}
```

### Feature Test Example

```php
public function test_user_can_start_learning_path()
{
    $user = User::factory()->create();
    $path = AILearningPath::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);
    
    $response = $this->actingAs($user)
        ->post(route('learning-paths.start', $path));
    
    $response->assertRedirect();
    $path->refresh();
    $this->assertEquals('active', $path->status);
}
```

## Error Handling

```php
try {
    $learningPath = $service->generateLearningPath($user, $targetRole);
} catch (AIServiceException $e) {
    Log::error('Learning path generation failed', [
        'user_id' => $user->id,
        'error' => $e->getMessage()
    ]);
    
    return back()->with('error', 'Failed to generate learning path. Please try again.');
}
```

## Performance Considerations

1. **Caching**: AI responses can be cached to reduce API calls
2. **Eager Loading**: Load courses and enrollments with the path
3. **Async Processing**: Consider queuing path generation for large datasets
4. **Progress Updates**: Update progress on course completion events

## Tips

- Always validate prerequisites before adding courses to a path
- Use milestones to break long paths into manageable chunks
- Provide clear reasoning for course selection to help users understand
- Monitor AI response quality and adjust prompts as needed
- Log all path adjustments for audit trail
- Consider user feedback when optimizing paths
