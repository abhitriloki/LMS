# AI Question Generator - Testing Guide

## Overview
This guide provides comprehensive testing instructions for the AI Question Generator feature.

## Prerequisites

1. Install dependencies:
```bash
composer install
composer require --dev smalot/pdfparser
```

2. Configure environment:
```bash
# .env
OPENAI_API_KEY=your_api_key_here
QUEUE_CONNECTION=redis
```

3. Run migrations:
```bash
php artisan migrate
```

4. Seed test data:
```bash
php artisan db:seed
```

## Unit Tests

### Run All Unit Tests
```bash
php artisan test tests/Unit/Services/AI/AIQuestionGeneratorServiceTest.php
```

### Run Specific Tests

#### Test Question Generation
```bash
php artisan test --filter=test_generates_questions_from_text_lesson
```

#### Test Parameter Validation
```bash
php artisan test --filter=test_validates_parameters
```

#### Test Content Extraction
```bash
php artisan test --filter=test_extracts_text_from_text_lesson
```

#### Test Question Formatting
```bash
php artisan test --filter=test_formats_multiple_choice_question
php artisan test --filter=test_formats_true_false_question
php artisan test --filter=test_formats_fill_in_blank_question
php artisan test --filter=test_formats_essay_question
```

#### Test Database Operations
```bash
php artisan test --filter=test_saves_generated_questions_to_database
```

#### Test Error Handling
```bash
php artisan test --filter=test_handles_invalid_json_response
php artisan test --filter=test_throws_exception_when_no_content_extracted
```

## Feature Tests

### Run All Feature Tests
```bash
php artisan test tests/Feature/AI/QuestionGeneratorTest.php
```

### Run Specific Workflows

#### Test Generation Workflow
```bash
php artisan test --filter=test_instructor_can_trigger_question_generation
php artisan test --filter=test_instructor_can_view_generation_status
```

#### Test Review Workflow
```bash
php artisan test --filter=test_instructor_can_review_generated_questions
php artisan test --filter=test_instructor_can_approve_generated_question
php artisan test --filter=test_instructor_can_reject_generated_question
```

#### Test Edit Workflow
```bash
php artisan test --filter=test_instructor_can_edit_generated_question
php artisan test --filter=test_instructor_can_update_generated_question
```

#### Test Bulk Operations
```bash
php artisan test --filter=test_instructor_can_bulk_approve_questions
```

#### Test Authorization
```bash
php artisan test --filter=test_unauthorized_user_cannot_access_question_generator
```

#### Test Job Processing
```bash
php artisan test --filter=test_question_generation_job_processes_successfully
```

## Manual Testing

### 1. Test Question Generation Form

1. Login as instructor
2. Navigate to a lesson
3. Click "Generate Questions with AI"
4. Verify form displays:
   - Lesson information
   - Question count input (1-20)
   - Question type checkboxes
   - Difficulty dropdown
   - Points input
   - Include explanation checkbox

### 2. Test Question Generation

1. Fill out the form:
   - Count: 5
   - Types: Multiple Choice, True/False
   - Difficulty: Medium
   - Points: 1
   - Include Explanation: Yes
2. Submit form
3. Verify redirect to status page
4. Verify job created in database
5. Verify job dispatched to queue

### 3. Test Status Page

1. Navigate to status page
2. Verify displays:
   - Lesson information
   - Current status (pending/processing/completed/failed)
   - Generation parameters
   - Progress indicator (if processing)
3. Wait for completion
4. Verify auto-refresh works
5. Verify "Review Questions" button appears when complete

### 4. Test Review Interface

1. Click "Review Questions"
2. Verify displays:
   - List of generated questions
   - Assessment selection dropdown
   - Bulk selection checkbox
   - Individual question cards with:
     - Question text
     - Question type badge
     - Options (if applicable)
     - Correct answer highlighted
     - Explanation
     - Approve/Edit/Reject buttons

### 5. Test Question Approval

1. Select an assessment
2. Click "Approve" on a question
3. Verify:
   - Question status updated to "approved"
   - Question added to assessment
   - Question removed from pending list
   - Success message displayed

### 6. Test Question Rejection

1. Click "Reject" on a question
2. Enter rejection reason
3. Verify:
   - Question status updated to "rejected"
   - Question removed from pending list
   - Rejection reason saved

### 7. Test Question Editing

1. Click "Edit" on a question
2. Verify edit form displays with current values
3. Modify:
   - Question text
   - Options
   - Correct answer
   - Explanation
   - Points
4. Save changes
5. Verify updates saved
6. Verify redirected back to review page

### 8. Test Bulk Approval

1. Select multiple questions using checkboxes
2. Select an assessment
3. Click "Approve Selected"
4. Verify:
   - All selected questions approved
   - All questions added to assessment
   - Success message with count
   - Questions removed from pending list

### 9. Test Different Question Types

#### Multiple Choice
- Verify 4 options displayed
- Verify correct answer highlighted
- Verify can select one correct answer

#### True/False
- Verify True/False options
- Verify correct answer highlighted

#### Fill in the Blank
- Verify [BLANK] in question text
- Verify correct answer displayed separately
- Verify multiple acceptable answers supported

#### Essay
- Verify no options displayed
- Verify explanation shows grading criteria

### 10. Test Content Extraction

#### Text Content
1. Create lesson with text content
2. Generate questions
3. Verify questions based on text

#### PDF Content
1. Upload PDF to lesson
2. Generate questions
3. Verify text extracted from PDF
4. Verify questions based on PDF content

#### Video Content
1. Create lesson with video
2. Add transcription to metadata
3. Generate questions
4. Verify questions based on transcription

### 11. Test Error Handling

#### No Content
1. Create lesson with no content
2. Try to generate questions
3. Verify error message displayed

#### Invalid Parameters
1. Submit form with invalid values
2. Verify validation errors displayed

#### AI Service Failure
1. Configure invalid API key
2. Try to generate questions
3. Verify job fails gracefully
4. Verify error notification sent
5. Verify error message displayed on status page

### 12. Test Notifications

#### Email Notification
1. Generate questions
2. Wait for completion
3. Check email inbox
4. Verify email received with:
   - Success message
   - Question count
   - Link to review page

#### Database Notification
1. Generate questions
2. Wait for completion
3. Check notifications table
4. Verify notification created with correct data

### 13. Test Authorization

#### As Instructor
- Can access all features
- Can only generate for own courses

#### As Admin
- Can access all features
- Can generate for any course

#### As Student
- Cannot access generator
- Gets 403 Forbidden

### 14. Test Queue Processing

1. Start queue worker:
```bash
php artisan queue:work --queue=ai-processing
```

2. Generate questions
3. Monitor queue worker output
4. Verify job processed
5. Verify questions created

### 15. Test Performance

#### Small Content (< 1000 words)
- Should complete in < 30 seconds

#### Medium Content (1000-5000 words)
- Should complete in < 60 seconds

#### Large Content (> 5000 words)
- Should complete in < 120 seconds

#### Multiple Concurrent Jobs
1. Generate questions for 5 lessons simultaneously
2. Verify all jobs process successfully
3. Verify no race conditions

## Integration Testing

### Test with Real AI Service

1. Configure valid OpenAI API key
2. Create lesson with real content
3. Generate questions
4. Verify:
   - Questions are relevant to content
   - Questions are well-formatted
   - Correct answers are accurate
   - Explanations are helpful
   - Difficulty matches request

### Test with Mock AI Service

1. Mock AI service in tests
2. Provide predefined responses
3. Verify system handles responses correctly

## Load Testing

### Test Rate Limiting

1. Generate questions rapidly
2. Verify rate limiting works
3. Verify appropriate error messages

### Test Concurrent Users

1. Simulate 10 instructors generating questions
2. Verify all requests processed
3. Verify no data corruption

## Regression Testing

After any changes, run:

```bash
# All tests
php artisan test

# AI-specific tests
php artisan test tests/Feature/AI tests/Unit/Services/AI

# Question generator tests only
php artisan test --filter=QuestionGenerator
```

## Test Data Setup

### Create Test Lesson with Content

```php
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseLesson;

$course = Course::factory()->create();
$module = CourseModule::factory()->create(['course_id' => $course->id]);
$lesson = CourseLesson::factory()->create([
    'module_id' => $module->id,
    'content_type' => 'text',
    'content_path' => 'Laravel is a web application framework with expressive, elegant syntax...',
]);
```

### Create Test Assessment

```php
use App\Models\Assessment;

$assessment = Assessment::factory()->create([
    'course_id' => $course->id,
    'title' => 'Test Assessment',
]);
```

## Troubleshooting Tests

### Tests Fail with "No API Key"
```bash
# Set in .env.testing
OPENAI_API_KEY=test_key
```

### Tests Fail with Database Errors
```bash
php artisan migrate:fresh --env=testing
```

### Tests Timeout
```bash
# Increase timeout in phpunit.xml
<env name="QUEUE_CONNECTION" value="sync"/>
```

### Mock Not Working
```php
// Ensure mock is bound before test
$this->app->instance(AIServiceInterface::class, $mockAI);
```

## Coverage Report

Generate coverage report:
```bash
php artisan test --coverage
php artisan test --coverage-html coverage
```

Target coverage: > 80%

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
        run: php artisan test
        env:
          OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
```

## Test Checklist

- [ ] All unit tests pass
- [ ] All feature tests pass
- [ ] Manual testing completed
- [ ] Authorization tested
- [ ] Error handling tested
- [ ] Notifications tested
- [ ] Queue processing tested
- [ ] Performance acceptable
- [ ] Documentation updated
- [ ] Code coverage > 80%

## Next Steps

After testing is complete:
1. Review test results
2. Fix any failing tests
3. Update documentation
4. Deploy to staging
5. Perform UAT
6. Deploy to production
