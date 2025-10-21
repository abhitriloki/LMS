# Enrollment System - Quick Reference

## User Routes
```
GET  /my-courses                    - View enrolled courses
GET  /courses/{course}/enroll       - Enrollment confirmation page
POST /courses/{course}/enroll       - Enroll in course
DELETE /courses/{course}/unenroll   - Unenroll from course
```

## Admin Routes
```
GET    /admin/enrollments                      - List all enrollments
GET    /admin/enrollments/create               - Bulk enrollment form
POST   /admin/enrollments                      - Process bulk enrollment
GET    /admin/enrollments/{enrollment}         - View enrollment details
GET    /admin/enrollments/{enrollment}/edit    - Edit enrollment
PUT    /admin/enrollments/{enrollment}         - Update enrollment
DELETE /admin/enrollments/{enrollment}         - Delete enrollment
GET    /admin/courses/{course}/enrollments     - Course enrollment stats
```

## EnrollmentService Methods

### Enrollment Operations
```php
// Enroll a user in a course
$enrollment = $enrollmentService->enroll($user, $course, $type = 'self', $enrolledBy = null, $deadline = null);

// Unenroll a user from a course
$enrollmentService->unenroll($user, $course);

// Bulk enroll users
$results = $enrollmentService->bulkEnroll($users, $course, $type, $enrolledBy, $deadline);

// Enroll entire department
$results = $enrollmentService->enrollDepartment($department, $course, $type, $enrolledBy, $deadline);
```

### Prerequisites
```php
// Check if user can enroll (prerequisites met)
$canEnroll = $enrollmentService->checkPrerequisites($user, $course);

// Get missing prerequisites
$missing = $enrollmentService->getMissingPrerequisites($user, $course);
```

### Mandatory Courses
```php
// Auto-enroll user in all mandatory courses
$count = $enrollmentService->autoEnrollMandatoryCourses($user);

// Auto-enroll all users in a mandatory course
$count = $enrollmentService->autoEnrollMandatoryCourses(null, $course);
```

### Status Management
```php
// Update enrollment status
$enrollment = $enrollmentService->updateStatus($enrollment, 'completed');

// Update deadline
$enrollment = $enrollmentService->updateDeadline($enrollment, $newDeadline);

// Check and update expired enrollments
$count = $enrollmentService->checkExpiredEnrollments();
```

### Analytics
```php
// Get user statistics
$stats = $enrollmentService->getUserStatistics($user);
// Returns: ['total', 'active', 'completed', 'overdue', 'average_progress']

// Get course statistics
$stats = $enrollmentService->getCourseStatistics($course);
// Returns: ['total', 'active', 'completed', 'completion_rate', 'average_progress']

// Get upcoming deadlines (default 7 days)
$enrollments = $enrollmentService->getUpcomingDeadlines($user, $days = 7);

// Get overdue enrollments
$enrollments = $enrollmentService->getOverdueEnrollments($user);
```

## Enrollment Types
- `self` - User-initiated enrollment
- `assigned` - Admin-assigned enrollment
- `mandatory` - Required course (cannot unenroll)

## Enrollment Statuses
- `active` - Currently enrolled and learning
- `completed` - Course finished
- `suspended` - Temporarily paused
- `expired` - Deadline passed without completion

## View Components

### User Dashboard (`enrollments/index.blade.php`)
- Statistics cards
- Filter tabs (all, active, completed)
- Course cards with progress
- Deadline alerts

### Enrollment Confirmation (`enrollments/show.blade.php`)
- Course details
- Prerequisite checking
- Learning objectives
- Enroll button

### Admin Management (`admin/enrollments/index.blade.php`)
- Advanced filtering
- Enrollment table
- Status indicators
- Quick actions

### Bulk Enrollment (`admin/enrollments/create.blade.php`)
- Course selection
- User/department targeting
- Enrollment type
- Deadline setting

## Testing
Run enrollment tests:
```bash
php artisan test --filter=EnrollmentTest
```

## Common Use Cases

### Enroll User in Course
```php
$user = Auth::user();
$course = Course::find($courseId);

try {
    $enrollment = $enrollmentService->enroll($user, $course);
    return redirect()->route('enrollments.index')
        ->with('success', 'Successfully enrolled!');
} catch (\Exception $e) {
    return back()->with('error', $e->getMessage());
}
```

### Bulk Enroll Department
```php
$department = Department::find($departmentId);
$course = Course::find($courseId);
$admin = Auth::user();
$deadline = now()->addDays(30);

$results = $enrollmentService->enrollDepartment(
    $department,
    $course,
    'mandatory',
    $admin,
    $deadline
);

// $results['success'] - array of successful enrollments
// $results['failed'] - array of failed enrollments with reasons
```

### Check Prerequisites Before Enrollment
```php
$canEnroll = $enrollmentService->checkPrerequisites($user, $course);

if (!$canEnroll) {
    $missing = $enrollmentService->getMissingPrerequisites($user, $course);
    // Display missing prerequisites to user
}
```

### Auto-Enroll New User in Mandatory Courses
```php
// In user registration or creation
$user = User::create($data);
$enrollmentService->autoEnrollMandatoryCourses($user);
```

## Database Queries

### Get User's Active Enrollments
```php
$enrollments = Enrollment::where('user_id', $user->id)
    ->where('status', 'active')
    ->with(['course', 'course.category'])
    ->get();
```

### Get Course Enrollments with Progress
```php
$enrollments = Enrollment::where('course_id', $course->id)
    ->with(['user', 'lessonProgress'])
    ->orderBy('progress_percentage', 'desc')
    ->get();
```

### Find Overdue Enrollments
```php
$overdue = Enrollment::where('deadline', '<', now())
    ->where('status', 'active')
    ->with(['user', 'course'])
    ->get();
```

## Navigation Links
- User: "My Courses" in sidebar → `/my-courses`
- Admin: "Enrollments" in sidebar → `/admin/enrollments`
- Course Catalog: "Enroll Now" button → `/courses/{course}/enroll`
