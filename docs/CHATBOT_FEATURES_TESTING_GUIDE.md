# AI Chatbot Features - Testing Guide

## Overview

This guide provides comprehensive testing instructions for the AI Chatbot features including course recommendations, progress inquiries, and enrollment assistance.

## Test Setup

### Prerequisites

1. Database with test data:
   - Users with different roles
   - Published courses with categories
   - Course enrollments with progress
   - AI recommendations
   - Course prerequisites

2. AI Service Mock:
   - Mock OpenAI service for predictable responses
   - Test intent detection
   - Test response generation

### Running Tests

```bash
# Run all chatbot tests
php artisan test --filter=Chatbot

# Run unit tests only
php artisan test --filter=AIChatbotServiceTest

# Run feature tests only
php artisan test --filter=ChatbotTest

# Run with coverage
php artisan test --filter=Chatbot --coverage
```

## Unit Tests

### Test File: `tests/Unit/Services/AI/AIChatbotServiceTest.php`

#### Intent Detection Tests

**Test: it_detects_greeting_intent**
- Verifies greeting messages are correctly identified
- Checks cache usage for performance

**Test: it_detects_course_recommendation_intent**
- Verifies recommendation requests are identified
- Tests various phrasings

**Test: it_detects_progress_inquiry_intent**
- Verifies progress questions are identified
- Tests different question formats

**Test: it_detects_enrollment_assistance_intent**
- Verifies enrollment requests are identified
- Tests enrollment-related phrases

**Test: it_caches_intent_detection_results**
- Verifies caching mechanism works
- Ensures AI service is called only once for same message

#### Message Processing Tests

**Test: it_processes_message_and_returns_response**
- Verifies complete message processing flow
- Checks response structure
- Validates all required fields

**Test: it_handles_greeting_messages**
- Verifies personalized greetings
- Checks user name inclusion

**Test: it_handles_help_requests**
- Verifies help response content
- Checks all features are listed

#### Course Recommendation Tests

**Test: it_provides_course_recommendations_with_ai_recommendations**
- Creates AI recommendations
- Verifies recommendations are included in response
- Checks reasoning is displayed

**Test: it_shows_user_progress_summary**
- Creates enrollments with progress
- Verifies progress summary is accurate
- Checks all metrics are included

**Test: it_shows_message_when_no_enrollments**
- Verifies appropriate message for new users
- Checks recommendation offer

#### Enrollment Assistance Tests

**Test: it_handles_enrollment_assistance_with_course_id**
- Verifies enrollment by course ID
- Checks course details are displayed
- Validates difficulty and duration formatting

**Test: it_detects_already_enrolled_status**
- Creates existing enrollment
- Verifies already enrolled message
- Checks progress is displayed

**Test: it_checks_prerequisites_before_enrollment**
- Creates course with prerequisites
- Verifies prerequisite check
- Checks prerequisite list is displayed

**Test: it_handles_enrollment_by_course_title**
- Verifies enrollment by course name
- Tests fuzzy matching

**Test: it_shows_multiple_courses_when_title_matches_many**
- Creates multiple matching courses
- Verifies all matches are shown
- Checks course IDs are included

#### Knowledge Base Tests

**Test: it_searches_knowledge_base**
- Verifies enrolled courses are found
- Checks available courses are found
- Validates user progress data

**Test: it_handles_specific_course_progress_inquiry**
- Creates course with lessons
- Creates enrollment with progress
- Verifies detailed progress information
- Checks lesson completion count

#### Error Handling Tests

**Test: it_returns_fallback_response_on_error**
- Simulates AI service error
- Verifies graceful error handling
- Checks fallback response

**Test: it_calculates_confidence_scores**
- Verifies confidence calculation
- Checks score is between 0 and 1

## Feature Tests

### Test File: `tests/Feature/AI/ChatbotTest.php`

#### API Endpoint Tests

**Test: user_can_send_message_to_chatbot**
- Tests POST /api/chatbot/message
- Verifies conversation creation
- Checks message storage
- Validates response structure

**Test: user_can_continue_existing_conversation**
- Tests message in existing conversation
- Verifies conversation_id is maintained
- Checks message count increases

**Test: user_can_start_new_conversation**
- Tests POST /api/chatbot/start
- Verifies conversation creation
- Checks initial status

**Test: user_can_get_conversation_history**
- Tests GET /api/chatbot/conversations/{id}
- Verifies all messages are returned
- Checks message order

**Test: user_can_get_all_conversations**
- Tests GET /api/chatbot/conversations
- Verifies pagination
- Checks conversation list

**Test: user_can_end_conversation**
- Tests POST /api/chatbot/conversations/{id}/end
- Verifies status change
- Checks ended_at timestamp

**Test: user_can_rate_conversation**
- Tests POST /api/chatbot/conversations/{id}/rate
- Verifies rating storage
- Checks feedback storage

**Test: user_can_delete_conversation**
- Tests DELETE /api/chatbot/conversations/{id}
- Verifies conversation deletion
- Checks cascade delete

#### Feature Integration Tests

**Test: chatbot_provides_course_recommendations**
- Creates AI recommendations
- Sends recommendation request
- Verifies course is mentioned in response

**Test: chatbot_shows_user_progress**
- Creates enrollments
- Sends progress inquiry
- Verifies progress data in response

**Test: chatbot_helps_with_enrollment**
- Creates published course
- Sends enrollment request
- Verifies course details in response

**Test: chatbot_detects_already_enrolled_courses**
- Creates enrollment
- Sends enrollment request
- Verifies already enrolled message

**Test: chatbot_checks_prerequisites**
- Creates course with prerequisites
- Sends enrollment request
- Verifies prerequisite message

#### Security Tests

**Test: user_cannot_access_other_users_conversations**
- Creates conversation for different user
- Attempts to access
- Verifies 404 response

**Test: guest_cannot_access_chatbot**
- Sends request without authentication
- Verifies 401 response

#### Validation Tests

**Test: message_validation_requires_message_content**
- Sends empty message
- Verifies validation error

**Test: message_validation_limits_message_length**
- Sends message over 1000 characters
- Verifies validation error

**Test: rating_validation_requires_valid_rating**
- Sends invalid rating (> 5)
- Verifies validation error

## Manual Testing Scenarios

### Scenario 1: Course Recommendation Flow

1. **Setup:**
   - Create user account
   - Create AI recommendations for user
   - Create published courses

2. **Test Steps:**
   ```
   User: "Can you recommend some courses?"
   Expected: List of recommended courses with reasoning
   
   User: "Tell me more about course 123"
   Expected: Detailed course information
   
   User: "Enroll me in course 123"
   Expected: Enrollment confirmation or prerequisite check
   ```

3. **Verification:**
   - Recommendations are personalized
   - Reasoning is clear and relevant
   - Course details are accurate
   - Enrollment process is smooth

### Scenario 2: Progress Tracking Flow

1. **Setup:**
   - Create user with enrollments
   - Set various progress levels
   - Add completed courses

2. **Test Steps:**
   ```
   User: "What is my progress?"
   Expected: Overall progress summary
   
   User: "How am I doing in Python Programming?"
   Expected: Specific course progress
   
   User: "Show my completed courses"
   Expected: List of completed courses
   ```

3. **Verification:**
   - Progress percentages are accurate
   - Completion status is correct
   - Deadlines are displayed
   - Motivational messages appear

### Scenario 3: Enrollment Assistance Flow

1. **Setup:**
   - Create published courses
   - Create courses with prerequisites
   - Create some enrollments

2. **Test Steps:**
   ```
   User: "I want to enroll in a course"
   Expected: Enrollment assistance options
   
   User: "Enroll in Python Programming"
   Expected: Course details and enrollment confirmation
   
   User: "Enroll in Advanced Python"
   Expected: Prerequisite check and requirements
   ```

3. **Verification:**
   - Course search works correctly
   - Prerequisites are validated
   - Already enrolled status is detected
   - Course details are complete

### Scenario 4: Error Handling

1. **Test Steps:**
   ```
   User: "Enroll in course 99999"
   Expected: Course not found message
   
   User: "What is my progress in course 99999?"
   Expected: Course not found message
   
   User: [Very long message]
   Expected: Validation error
   ```

2. **Verification:**
   - Error messages are helpful
   - No system errors occur
   - Fallback responses work

## Test Data Setup

### Minimal Test Data

```php
// Create test user
$user = User::factory()->create([
    'role' => 'employee',
    'department_id' => 1,
]);

// Create category
$category = CourseCategory::factory()->create([
    'name' => 'Programming',
]);

// Create courses
$course1 = Course::factory()->create([
    'title' => 'Python Basics',
    'category_id' => $category->id,
    'is_published' => true,
    'difficulty_level' => 'beginner',
]);

$course2 = Course::factory()->create([
    'title' => 'Advanced Python',
    'category_id' => $category->id,
    'is_published' => true,
    'difficulty_level' => 'advanced',
    'prerequisites' => [$course1->id],
]);

// Create enrollment
Enrollment::factory()->create([
    'user_id' => $user->id,
    'course_id' => $course1->id,
    'status' => 'in_progress',
    'progress_percentage' => 50,
]);

// Create AI recommendation
AIRecommendation::factory()->create([
    'user_id' => $user->id,
    'course_id' => $course2->id,
    'status' => 'pending',
    'relevance_score' => 0.95,
    'reasoning' => 'Perfect next step after Python Basics',
]);
```

## Performance Testing

### Load Testing

Test chatbot performance under load:

```bash
# Using Apache Bench
ab -n 1000 -c 10 -H "Authorization: Bearer {token}" \
   -p message.json \
   http://localhost/api/chatbot/message
```

### Response Time Benchmarks

- Intent detection: < 500ms (with cache)
- Message processing: < 2s
- Knowledge base search: < 1s
- Response generation: < 3s

## Debugging Tips

### Enable Debug Logging

```php
// In AIChatbotService
Log::debug('Processing message', [
    'message' => $message,
    'user_id' => $user->id,
    'intent' => $intent,
    'entities' => $entities,
]);
```

### Check AI Service Calls

```php
// Mock AI service in tests
$this->mockAIService->shouldReceive('generateText')
    ->once()
    ->with(Mockery::type('string'), Mockery::type('array'))
    ->andReturn('expected_response');
```

### Verify Database State

```php
// Check conversation creation
$this->assertDatabaseHas('chatbot_conversations', [
    'user_id' => $user->id,
    'status' => 'active',
]);

// Check message storage
$this->assertDatabaseHas('chatbot_messages', [
    'conversation_id' => $conversation->id,
    'role' => 'user',
    'content' => $message,
]);
```

## Common Issues and Solutions

### Issue: Intent not detected
**Solution:** Check AI service mock returns valid intent name

### Issue: Course not found
**Solution:** Ensure course is published and ID is correct

### Issue: Progress not showing
**Solution:** Verify enrollment exists with progress data

### Issue: Prerequisites not checked
**Solution:** Ensure prerequisites array is set in course

### Issue: Tests failing randomly
**Solution:** Clear cache before tests: `Cache::flush()`

## Continuous Integration

### GitHub Actions Example

```yaml
name: Chatbot Tests

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
        run: php artisan test --filter=Chatbot
```

## Test Coverage Goals

- **Unit Tests**: > 90% coverage
- **Feature Tests**: > 80% coverage
- **Integration Tests**: All critical paths covered

## Related Documentation

- [Chatbot Features Summary](./CHATBOT_FEATURES_SUMMARY.md)
- [Chatbot Features Quick Reference](./CHATBOT_FEATURES_QUICK_REFERENCE.md)
- [AI Service Foundation](./AI_SERVICE_FOUNDATION_SUMMARY.md)
