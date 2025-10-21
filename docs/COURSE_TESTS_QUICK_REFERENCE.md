# Course Management Tests - Quick Reference

## Test File Location
`tests/Feature/CourseManagementTest.php`

## Running Tests

### Run All Course Management Tests
```bash
php artisan test --filter=CourseManagementTest
```

### Run Specific Test Categories

#### Course CRUD Tests
```bash
php artisan test --filter=CourseManagementTest::test_instructor_can_create_course
php artisan test --filter=CourseManagementTest::test_instructor_can_update_own_course
php artisan test --filter=CourseManagementTest::test_instructor_can_delete_own_course_without_enrollments
```

#### Publishing Tests
```bash
php artisan test --filter=CourseManagementTest::test_course_can_be_published_when_valid
php artisan test --filter=CourseManagementTest::test_course_cannot_be_published_without_modules
```

#### Cloning Tests
```bash
php artisan test --filter=CourseManagementTest::test_course_can_be_cloned_with_modules_and_lessons
```

#### Prerequisite Tests
```bash
php artisan test --filter=CourseManagementTest::test_course_can_have_valid_prerequisites
php artisan test --filter=CourseManagementTest::test_circular_prerequisite_dependencies_are_detected
php artisan test --filter=CourseManagementTest::test_user_meets_prerequisites_when_completed
```

#### Module Tests
```bash
php artisan test --filter=CourseManagementTest::test_module_can_be_created_for_course
php artisan test --filter=CourseManagementTest::test_modules_can_be_reordered
php artisan test --filter=CourseManagementTest::test_module_can_be_duplicated
```

#### Lesson Tests
```bash
php artisan test --filter=CourseManagementTest::test_lesson_can_be_created_for_module
php artisan test --filter=CourseManagementTest::test_lessons_can_be_reordered
php artisan test --filter=CourseManagementTest::test_lesson_content_can_be_uploaded
```

## Test Statistics

- **Total Tests**: 47
- **Course CRUD**: 9 tests
- **Publishing**: 4 tests
- **Cloning**: 2 tests
- **Prerequisites**: 7 tests
- **Modules**: 7 tests
- **Lessons**: 10 tests
- **Authorization**: 8 tests

## Factory Usage

### Creating Test Data

```php
// Create a course
$course = Course::factory()->create();

// Create a published course
$course = Course::factory()->published()->create();

// Create a course with prerequisites
$prereq = Course::factory()->create();
$course = Course::factory()->withPrerequisites([$prereq->id])->create();

// Create a module
$module = CourseModule::factory()->forCourse($course)->create();

// Create a lesson
$lesson = CourseLesson::factory()->forModule($module)->video()->create();

// Create an enrollment
$enrollment = Enrollment::factory()->completed()->create([
    'user_id' => $user->id,
    'course_id' => $course->id,
]);
```

## Common Test Patterns

### Testing Authorization
```php
$instructor = User::factory()->create(['role' => 'instructor']);
$course = Course::factory()->create(['created_by' => $instructor->id]);

$response = $this->actingAs($instructor)
    ->get(route('admin.courses.show', $course));

$response->assertStatus(200);
```

### Testing Service Methods
```php
$service = app(CourseService::class);
$course = $service->createCourse($data, $instructor);

$this->assertDatabaseHas('courses', [
    'title' => $data['title'],
]);
```

### Testing File Uploads
```php
Storage::fake('public');
$file = UploadedFile::fake()->create('video.mp4', 1024);

$service = app(LessonService::class);
$lesson = $service->uploadContent($lesson, $file, 'video');

Storage::disk('public')->assertExists($lesson->content_path);
```

## Requirements Coverage

| Requirement | Description | Tests |
|-------------|-------------|-------|
| 2.1 | Course Management | 9 tests |
| 2.2 | Module & Lesson Management | 17 tests |
| 2.3 | Prerequisites | 7 tests |
| 2.10 | Course Cloning | 2 tests |

## Troubleshooting

### Database Issues
If tests fail due to database issues:
```bash
php artisan migrate:fresh
php artisan test --filter=CourseManagementTest
```

### Storage Issues
Storage is automatically faked in tests using:
```php
Storage::fake('public');
```

### Factory Issues
If factories fail, ensure all models have the `HasFactory` trait:
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;
    // ...
}
```

## Next Steps

After running tests successfully:
1. Review test coverage report
2. Add additional edge case tests if needed
3. Integrate with CI/CD pipeline
4. Set up code coverage monitoring

## Related Documentation

- [Task 8.6 Summary](./TASK_8.6_SUMMARY.md)
- [Course Management Module](../app/Services/CourseService.php)
- [Module Service](../app/Services/ModuleService.php)
- [Lesson Service](../app/Services/LessonService.php)
