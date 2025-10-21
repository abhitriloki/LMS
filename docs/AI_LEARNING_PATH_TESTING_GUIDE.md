# AI Learning Path Optimizer - Testing Guide

## Overview

This guide provides comprehensive testing instructions for the AI Learning Path Optimizer feature.

## Test Files

- `tests/Unit/Services/AI/AILearningPathServiceTest.php` - Unit tests for the service layer
- `tests/Feature/LearningPathTest.php` - Feature tests for the controller and routes

## Running Tests

### Run All Learning Path Tests
```bash
php artisan test --filter=LearningPath
```

### Run Unit Tests Only
```bash
php artisan test tests/Unit/Services/AI/AILearningPathServiceTest.php
```

### Run Feature Tests Only
```bash
php artisan test tests/Feature/LearningPathTest.php
```

### Run Specific Test
```bash
php artisan test --filter=test_generates_learning_path
```

## Unit Tests

### AILearningPathServiceTest

#### Test: it_generates_learning_path_for_user
**Purpose**: Verify that the service can generate a complete learning path

**Setup**:
- Create user with department
- Create published courses
- Mock AI service response

**Assertions**:
- Learning path is created
- Correct user association
- Correct target role
- Draft status
- Correct estimated duration
- Correct number of courses

#### Test: it_validates_prerequisites_in_learning_path
**Purpose**: Ensure prerequisites are validated and enforced

**Setup**:
- Create courses with prerequisites
- Mock AI response with wrong order

**Assertions**:
- Only courses with met prerequisites are included
- Courses are properly ordered

#### Test: it_optimizes_existing_learning_path
**Purpose**: Verify path optimization based on progress

**Setup**:
- Create learning path with courses
- Complete one course
- Mock optimized AI response

**Assertions**:
- Completed courses are removed
- Path is updated
- Last adjusted timestamp is set

#### Test: it_adjusts_path_based_on_performance
**Purpose**: Verify path adjustment based on performance data

**Setup**:
- Create active learning path
- Provide performance data (low scores)
- Mock adjusted AI response

**Assertions**:
- Path is adjusted
- Adjustment is recorded
- Last adjusted timestamp is set

#### Test: it_updates_progress_based_on_completed_courses
**Purpose**: Verify progress calculation

**Setup**:
- Create path with 2 courses
- Complete 1 course

**Assertions**:
- Progress is 50%

#### Test: it_gets_next_course_in_path
**Purpose**: Verify next course identification

**Setup**:
- Create path with 2 courses
- Complete first course

**Assertions**:
- Next course is the second one

#### Test: it_returns_null_when_all_courses_completed
**Purpose**: Verify behavior when path is complete

**Setup**:
- Create path with 1 course
- Complete the course

**Assertions**:
- Next course is null

#### Test: it_throws_exception_on_ai_service_failure
**Purpose**: Verify error handling

**Setup**:
- Mock AI service to throw exception

**Assertions**:
- AIServiceException is thrown

#### Test: it_throws_exception_on_invalid_ai_response
**Purpose**: Verify JSON parsing error handling

**Setup**:
- Mock AI service to return invalid JSON

**Assertions**:
- AIServiceException is thrown with parse error message

## Feature Tests

### LearningPathTest

#### Test: user_can_view_learning_paths_index
**Purpose**: Verify index page access

**Assertions**:
- 200 status
- Correct view
- Learning paths data present

#### Test: user_can_view_create_learning_path_form
**Purpose**: Verify create form access

**Assertions**:
- 200 status
- Correct view

#### Test: user_can_generate_learning_path
**Purpose**: Verify path generation workflow

**Setup**:
- Mock AI service
- Submit target role

**Assertions**:
- Redirect to show page
- Success message
- Database record created

#### Test: user_can_view_learning_path_details
**Purpose**: Verify show page access

**Assertions**:
- 200 status
- Correct view
- All required data present

#### Test: user_cannot_view_another_users_learning_path
**Purpose**: Verify authorization

**Setup**:
- Create path for user2
- Try to access as user1

**Assertions**:
- 403 status

#### Test: user_can_start_learning_path
**Purpose**: Verify path start action

**Assertions**:
- Redirect with success
- Status changed to active
- Started timestamp set

#### Test: user_can_pause_learning_path
**Purpose**: Verify path pause action

**Assertions**:
- Redirect with success
- Status changed to paused

#### Test: user_can_resume_learning_path
**Purpose**: Verify path resume action

**Assertions**:
- Redirect with success
- Status changed to active

#### Test: user_can_optimize_learning_path
**Purpose**: Verify optimization workflow

**Setup**:
- Mock AI service

**Assertions**:
- Redirect with success
- Last adjusted timestamp updated

#### Test: user_can_adjust_learning_path_based_on_performance
**Purpose**: Verify adjustment workflow

**Setup**:
- Mock AI service
- Submit performance data

**Assertions**:
- Redirect with success

#### Test: user_can_update_learning_path_progress
**Purpose**: Verify progress update endpoint

**Setup**:
- Complete one of two courses

**Assertions**:
- JSON response with success
- Progress updated to 50%

#### Test: user_can_delete_learning_path
**Purpose**: Verify deletion

**Assertions**:
- Redirect to index
- Success message
- Database record deleted

#### Test: admin_can_view_any_users_learning_path
**Purpose**: Verify admin access

**Setup**:
- Create admin user
- Create path for regular user

**Assertions**:
- 200 status (admin can view)

#### Test: validation_fails_without_target_role
**Purpose**: Verify form validation

**Assertions**:
- Validation error for target_role

#### Test: guest_cannot_access_learning_paths
**Purpose**: Verify authentication requirement

**Assertions**:
- Redirect to login

## Manual Testing Checklist

### Path Generation
- [ ] Navigate to "Create Learning Path"
- [ ] Enter target role (e.g., "Senior Developer")
- [ ] Click "Generate Learning Path"
- [ ] Verify path is created with courses
- [ ] Check AI reasoning is displayed
- [ ] Verify estimated duration is shown

### Path Visualization
- [ ] View learning path details
- [ ] Verify courses are displayed in order
- [ ] Check milestone indicators
- [ ] Verify progress bar shows 0%
- [ ] Check next course is highlighted
- [ ] Verify locked courses show lock icon

### Path Lifecycle
- [ ] Click "Start Path" on draft path
- [ ] Verify status changes to "Active"
- [ ] Click "Pause Path"
- [ ] Verify status changes to "Paused"
- [ ] Click "Resume Path"
- [ ] Verify status changes back to "Active"

### Course Enrollment
- [ ] Click "Enroll Now" on next course
- [ ] Complete the course
- [ ] Return to learning path
- [ ] Verify progress updated
- [ ] Check next course is now highlighted

### Path Optimization
- [ ] Complete at least one course
- [ ] Click "Optimize" button
- [ ] Verify path is updated
- [ ] Check completed courses are handled
- [ ] Verify new courses may be added

### Progress Tracking
- [ ] Complete multiple courses
- [ ] Click "Refresh Progress"
- [ ] Verify progress percentage updates
- [ ] Check progress bar reflects changes
- [ ] Verify completed courses show checkmark

### Authorization
- [ ] Try to view another user's path (should fail)
- [ ] Login as admin
- [ ] View another user's path (should succeed)
- [ ] Try to modify another user's path as regular user (should fail)

### Error Handling
- [ ] Submit empty target role (should show validation error)
- [ ] Simulate AI service failure (should show error message)
- [ ] Try to access non-existent path (should show 404)

## Test Data Setup

### Create Test Users
```php
$user = User::factory()->create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'position' => 'Junior Developer',
]);

$admin = User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'role' => 'super_admin',
]);
```

### Create Test Courses
```php
$course1 = Course::factory()->create([
    'title' => 'PHP Basics',
    'is_published' => true,
    'difficulty_level' => 'beginner',
    'estimated_duration' => 4,
    'tags' => ['PHP', 'Programming'],
]);

$course2 = Course::factory()->create([
    'title' => 'Advanced PHP',
    'is_published' => true,
    'difficulty_level' => 'advanced',
    'estimated_duration' => 6,
    'prerequisites' => [$course1->id],
    'tags' => ['PHP', 'Advanced'],
]);
```

### Create Test Learning Path
```php
$learningPath = AILearningPath::factory()
    ->withCourses([$course1->id, $course2->id])
    ->create([
        'user_id' => $user->id,
        'target_role' => 'Senior Developer',
        'status' => 'active',
    ]);
```

## Mocking AI Service

### Mock Successful Response
```php
$aiService = Mockery::mock(AIServiceInterface::class);
$aiService->shouldReceive('generateText')
    ->once()
    ->andReturn(json_encode([
        'reasoning' => 'This path will help you become a Senior Developer',
        'courses' => [
            [
                'course_id' => $course1->id,
                'order' => 1,
                'milestone' => 'Foundation',
                'reason' => 'Learn PHP basics',
                'estimated_weeks' => 4,
            ],
        ],
        'milestones' => [
            [
                'name' => 'Foundation Complete',
                'course_ids' => [$course1->id],
                'description' => 'Basic skills acquired',
            ],
        ],
        'total_estimated_weeks' => 4,
    ]));

$this->app->instance(AIServiceInterface::class, $aiService);
```

### Mock AI Service Failure
```php
$aiService = Mockery::mock(AIServiceInterface::class);
$aiService->shouldReceive('generateText')
    ->once()
    ->andThrow(new AIServiceException('AI service unavailable'));

$this->app->instance(AIServiceInterface::class, $aiService);
```

## Expected Test Results

All tests should pass with:
- ✓ Unit tests: 9 passing
- ✓ Feature tests: 15 passing
- ✓ Total: 24 passing

## Common Issues and Solutions

### Issue: AI Service Not Mocked
**Solution**: Ensure AI service is mocked in setUp() method or before test execution

### Issue: Database Constraints
**Solution**: Use RefreshDatabase trait and ensure foreign keys are properly set

### Issue: Route Not Found
**Solution**: Verify routes are registered in routes/web.php

### Issue: Authorization Failures
**Solution**: Check LearningPathPolicy is registered in AuthServiceProvider

### Issue: Factory Not Found
**Solution**: Ensure AILearningPathFactory exists and is properly namespaced

## Performance Testing

### Load Testing
Test with multiple concurrent users:
```bash
# Using Apache Bench
ab -n 100 -c 10 http://localhost/learning-paths
```

### Database Query Optimization
Check N+1 queries:
```php
DB::enableQueryLog();
// Perform action
dd(DB::getQueryLog());
```

## Continuous Integration

Add to CI pipeline:
```yaml
- name: Run Learning Path Tests
  run: php artisan test --filter=LearningPath --coverage
```

## Test Coverage

Aim for:
- Service layer: 100% coverage
- Controller layer: 90%+ coverage
- Model methods: 100% coverage
- Views: Manual testing

Check coverage:
```bash
php artisan test --coverage --min=80
```
