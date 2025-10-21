# Content Delivery System - Quick Reference

## Overview
The Content Delivery System provides a comprehensive solution for delivering course content (video, PDF, text) with progress tracking, bookmarking, and completion management.

## Key Components

### 1. ContentService
**Location:** `app/Services/ContentService.php`

**Purpose:** Core business logic for content delivery and progress tracking.

**Key Methods:**
```php
// Get lesson content for a user
getLesson(int $lessonId, User $user): array

// Track progress during content consumption
trackProgress(User $user, CourseLesson $lesson, array $data): LessonProgress

// Save bookmark position
bookmarkPosition(User $user, CourseLesson $lesson, int $position): LessonProgress

// Mark lesson as complete
markComplete(User $user, CourseLesson $lesson): LessonProgress

// Get download URL for content
getDownloadUrl(CourseLesson $lesson, User $user): ?string

// Navigation helpers
getNextLesson(CourseLesson $currentLesson): ?CourseLesson
getPreviousLesson(CourseLesson $currentLesson): ?CourseLesson
```

### 2. ProgressTrackingService
**Location:** `app/Services/ProgressTrackingService.php`

**Purpose:** Calculate and manage enrollment progress across courses.

**Key Methods:**
```php
// Update enrollment progress based on completed lessons
updateEnrollmentProgress(Enrollment $enrollment): array

// Calculate course completion percentage
calculateCourseCompletion(Enrollment $enrollment): float

// Get progress statistics
getProgressStats(Enrollment $enrollment): array

// Get detailed progress with module breakdown
getDetailedProgress(Enrollment $enrollment): array

// Get user progress across all enrollments
getUserProgress(User $user): array

// Recalculate all progress (maintenance)
recalculateAllProgress(): array
```

### 3. LessonViewController
**Location:** `app/Http/Controllers/LessonViewController.php`

**Purpose:** Handle HTTP requests for lesson viewing and progress updates.

**Routes:**
```php
GET  /lessons/{lesson}              // View lesson
POST /lessons/{lesson}/progress     // Update progress
POST /lessons/{lesson}/bookmark     // Save bookmark
GET  /lessons/{lesson}/bookmark     // Get bookmark
POST /lessons/{lesson}/complete     // Mark complete
GET  /lessons/{lesson}/download     // Download content
```

### 4. UpdateEnrollmentProgressJob
**Location:** `app/Jobs/UpdateEnrollmentProgressJob.php`

**Purpose:** Asynchronously update enrollment progress after lesson progress changes.

**Usage:**
```php
UpdateEnrollmentProgressJob::dispatch($enrollment);
```

## Frontend Components

### 1. Video Player Component
**Location:** `resources/views/components/video-player.blade.php`

**Features:**
- Video.js integration
- Playback controls and quality selection
- Automatic progress tracking
- Bookmark restoration
- Auto-complete on video end

**Usage:**
```blade
<x-video-player 
    :videoUrl="$videoUrl"
    :lessonId="$lesson->id"
    :lastPosition="$progress->last_position ?? 0"
/>
```

### 2. PDF Viewer Component
**Location:** `resources/views/components/pdf-viewer.blade.php`

**Features:**
- PDF.js integration
- Page navigation and zoom controls
- Annotation functionality
- Page bookmarking
- Progress tracking

**Usage:**
```blade
<x-pdf-viewer 
    :pdfUrl="$pdfUrl"
    :lessonId="$lesson->id"
    :lastPosition="$progress->last_position ?? 1"
/>
```

### 3. Lesson Navigation Component
**Location:** `resources/views/components/lesson-navigation.blade.php`

**Features:**
- Module/lesson tree view
- Progress indicators
- Current lesson highlighting
- Completion status badges

**Usage:**
```blade
<x-lesson-navigation 
    :modules="$modules" 
    :currentLesson="$lesson" 
    :enrollment="$enrollment" 
/>
```

### 4. Lesson Viewer Page
**Location:** `resources/views/lessons/show.blade.php`

**Features:**
- Responsive layout with sidebar navigation
- Content type detection and rendering
- Next/previous navigation
- Download functionality
- Mobile-friendly design

## API Endpoints

### Update Progress
```http
POST /lessons/{lesson}/progress
Content-Type: application/json

{
    "progress_percentage": 75.5,
    "position": 180,
    "time_spent": 180
}
```

**Response:**
```json
{
    "success": true,
    "message": "Progress updated successfully",
    "data": {
        "progress_percentage": 75.5,
        "status": "in_progress",
        "last_position": 180
    }
}
```

### Save Bookmark
```http
POST /lessons/{lesson}/bookmark
Content-Type: application/json

{
    "position": 250
}
```

**Response:**
```json
{
    "success": true,
    "message": "Bookmark saved successfully",
    "data": {
        "last_position": 250
    }
}
```

### Get Bookmark
```http
GET /lessons/{lesson}/bookmark
```

**Response:**
```json
{
    "success": true,
    "data": {
        "last_position": 250,
        "progress_percentage": 75.5
    }
}
```

### Mark Complete
```http
POST /lessons/{lesson}/complete
```

**Response:**
```json
{
    "success": true,
    "message": "Lesson marked as complete",
    "data": {
        "status": "completed",
        "progress_percentage": 100,
        "completed_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

### Download Content
```http
GET /lessons/{lesson}/download
```

**Response:**
```json
{
    "success": true,
    "data": {
        "download_url": "https://..."
    }
}
```

## Database Schema

### lesson_progress Table
```sql
- id (bigint, primary key)
- enrollment_id (bigint, foreign key)
- lesson_id (bigint, foreign key)
- status (enum: not_started, in_progress, completed)
- progress_percentage (decimal)
- time_spent (integer, seconds)
- last_position (integer, seconds or page number)
- completed_at (timestamp, nullable)
- first_accessed_at (timestamp)
- last_accessed_at (timestamp)
- created_at (timestamp)
- updated_at (timestamp)
```

## Progress Status Flow

```
not_started (0%)
    ↓
in_progress (1-99%)
    ↓
completed (100%)
```

## Events

### CourseCompleted
**Location:** `app/Events/CourseCompleted.php`

**Triggered:** When a user completes all lessons in a course

**Payload:**
```php
public Enrollment $enrollment;
```

**Listeners:** Can be used to trigger certificate generation, notifications, etc.

## Common Use Cases

### 1. Display Lesson Content
```php
$data = $contentService->getLesson($lessonId, $user);
return view('lessons.show', $data);
```

### 2. Track Video Progress
```javascript
// In video player component
async function updateProgress(percentage, position) {
    await fetch(`/lessons/${lessonId}/progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            progress_percentage: percentage,
            position: position
        })
    });
}
```

### 3. Get Course Progress
```php
$stats = $progressService->getProgressStats($enrollment);
// Returns: total_lessons, completed_lessons, in_progress_lessons, 
//          not_started_lessons, progress_percentage, total_time_spent
```

### 4. Check if User Can Access Next Lesson
```php
$nextLesson = $contentService->getNextLesson($currentLesson);
if ($nextLesson) {
    // Show next lesson button
}
```

## Configuration

### Video Player Settings
Located in `resources/views/components/video-player.blade.php`:
- Progress update interval: Every 5% change
- Bookmark save interval: Every 30 seconds
- Playback rates: 0.5x, 0.75x, 1x, 1.25x, 1.5x, 2x

### PDF Viewer Settings
Located in `resources/views/components/pdf-viewer.blade.php`:
- Default scale: 1.5
- Progress update interval: Every 30 seconds
- Annotation color: Blue (#3B82F6)

## Testing

### Run Content Delivery Tests
```bash
php artisan test tests/Feature/ContentDeliveryTest.php
```

### Run Progress Tracking Tests
```bash
php artisan test tests/Feature/ProgressTrackingTest.php
```

## Troubleshooting

### Progress Not Updating
1. Check if user is enrolled in the course
2. Verify CSRF token is included in requests
3. Check queue is running: `php artisan queue:work`
4. Review logs: `storage/logs/laravel.log`

### Bookmark Not Restoring
1. Verify bookmark was saved successfully
2. Check last_position value in database
3. Ensure player initialization waits for bookmark data

### Course Not Marking as Complete
1. Verify all lessons are marked as completed
2. Check enrollment status is not already 'completed'
3. Ensure CourseCompleted event listener is registered

## Performance Considerations

1. **Async Processing:** Progress updates are queued to avoid blocking user interactions
2. **Caching:** Consider caching course structure and progress data
3. **Batch Updates:** Bookmark saves are throttled to reduce database writes
4. **Lazy Loading:** Modules and lessons are loaded on-demand

## Security

1. **Enrollment Verification:** All content access checks enrollment status
2. **Download URLs:** Temporary signed URLs for secure file access
3. **CSRF Protection:** All POST requests require CSRF token
4. **Authorization:** Policies ensure users can only access their own progress

## Related Documentation

- [Enrollment System Summary](ENROLLMENT_SYSTEM_SUMMARY.md)
- [Content Delivery Tests Summary](CONTENT_DELIVERY_TESTS_SUMMARY.md)
- [Course Management Quick Reference](COURSE_TESTS_QUICK_REFERENCE.md)

## Next Steps

After implementing the content delivery system:
1. ✅ Test all content types (video, PDF, text)
2. ✅ Verify progress tracking accuracy
3. ✅ Test bookmark restoration
4. ✅ Validate course completion flow
5. → Implement Assessment Engine (Task 12)
