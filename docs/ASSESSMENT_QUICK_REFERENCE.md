# Assessment Engine Quick Reference

## Routes

### Admin Routes
```
GET    /admin/assessments                    - List assessments
GET    /admin/assessments/create             - Create form
POST   /admin/assessments                    - Store assessment
GET    /admin/assessments/{id}               - View assessment
GET    /admin/assessments/{id}/edit          - Edit form
PUT    /admin/assessments/{id}               - Update assessment
DELETE /admin/assessments/{id}               - Delete assessment
PUT    /admin/assessments/{id}/publish       - Publish
PUT    /admin/assessments/{id}/unpublish     - Unpublish
POST   /admin/assessments/{id}/clone         - Clone assessment
GET    /admin/assessments/{id}/attempts      - View attempts

GET    /admin/assessments/{id}/questions/create           - Add question
POST   /admin/assessments/{id}/questions                  - Store question
GET    /admin/assessments/{id}/questions/{qid}/edit       - Edit question
PUT    /admin/assessments/{id}/questions/{qid}            - Update question
DELETE /admin/assessments/{id}/questions/{qid}            - Delete question
POST   /admin/assessments/{id}/questions/reorder          - Reorder questions
POST   /admin/assessments/{id}/questions/{qid}/clone      - Clone question
POST   /admin/assessments/{id}/questions/bulk-import      - Bulk import

GET    /admin/attempts/{id}/grade            - Grading interface
POST   /admin/attempts/{id}/save-grade       - Save grades
```

### Student Routes
```
GET    /assessments/{id}                     - View assessment
GET    /assessments/{id}/start               - Start page
POST   /assessments/{id}/begin               - Begin attempt
GET    /attempts/{id}/take                   - Take assessment
POST   /attempts/{id}/questions/{qid}/response - Save response
POST   /attempts/{id}/submit                 - Submit attempt
GET    /attempts/{id}/results                - View results
GET    /attempts/{id}/review                 - Review answers
GET    /attempts/{id}/remaining-time         - Get remaining time (AJAX)
GET    /my-attempts                          - Attempt history
```

## Service Methods

### AssessmentService
```php
// CRUD
createAssessment(array $data, User $creator): Assessment
updateAssessment(Assessment $assessment, array $data): Assessment
deleteAssessment(Assessment $assessment): bool
getAssessment(int $id): ?Assessment
getAssessmentWithQuestions(int $id): ?Assessment

// Publishing
publishAssessment(Assessment $assessment): Assessment
unpublishAssessment(Assessment $assessment): Assessment

// Operations
cloneAssessment(Assessment $assessment, User $creator): Assessment
searchAssessments(array $filters): LengthAwarePaginator

// Access Control
canUserAccessAssessment(Assessment $assessment, User $user): bool
canUserAttemptAssessment(Assessment $assessment, User $user): bool

// Statistics
getAssessmentStatistics(Assessment $assessment): array
```

### QuestionService
```php
// CRUD
createQuestion(Assessment $assessment, array $data): Question
updateQuestion(Question $question, array $data): Question
deleteQuestion(Question $question): bool

// Options
addOption(Question $question, array $data): QuestionOption
updateOption(QuestionOption $option, array $data): QuestionOption
deleteOption(QuestionOption $option): bool

// Operations
reorderQuestions(Assessment $assessment, array $questionIds): void
cloneQuestion(Question $question, Assessment $targetAssessment): Question
bulkImportQuestions(Assessment $assessment, array $questions): Collection

// Display
getRandomizedQuestions(Assessment $assessment, ?int $count = null): Collection
getQuestionsForDisplay(Assessment $assessment): Collection

// Validation
validateResponse(Question $question, $response): bool
calculateScore(Question $question, $response): float

// Statistics
getQuestionStatistics(Question $question): array
getQuestionBank(int $courseId): Collection
```

### AttemptService
```php
// Attempt Management
startAttempt(Assessment $assessment, User $user): AssessmentAttempt
getAttempt(int $attemptId): ?AssessmentAttempt
getAttemptWithQuestions(AssessmentAttempt $attempt): AssessmentAttempt
getUserAttempts(Assessment $assessment, User $user): Collection

// Response Management
saveResponse(AssessmentAttempt $attempt, Question $question, $responseData): AttemptResponse

// Submission & Grading
submitAttempt(AssessmentAttempt $attempt): AssessmentAttempt
autoSubmitAttempt(AssessmentAttempt $attempt): AssessmentAttempt
gradeAttempt(AssessmentAttempt $attempt): AssessmentAttempt
manuallyGradeResponse(AttemptResponse $response, float $points, ?string $feedback, ?User $gradedBy): AttemptResponse
finalizeGrading(AssessmentAttempt $attempt, ?User $gradedBy): AssessmentAttempt

// Results & Review
getAttemptResults(AssessmentAttempt $attempt): array
canReviewAttempt(AssessmentAttempt $attempt, User $user): bool

// Utilities
getRemainingTime(AssessmentAttempt $attempt): ?int
deleteAttempt(AssessmentAttempt $attempt): bool
getAttemptsRequiringGrading(Assessment $assessment): Collection
getUserAttemptStatistics(Assessment $assessment, User $user): array
```

## Model Methods

### Assessment
```php
// Relationships
course(): BelongsTo
questions(): HasMany
attempts(): HasMany
creator(): BelongsTo

// Scopes
scopePublished(Builder $query): Builder

// Configuration
hasTimeLimit(): bool
hasAttemptLimit(): bool
shouldRandomizeQuestions(): bool
shouldRandomizeOptions(): bool
shouldShowResults(): bool
shouldShowCorrectAnswers(): bool
allowsReview(): bool

// Calculations
getTotalQuestions(): int
getTotalPoints(): float
canUserAttempt(User $user): bool
getRemainingAttempts(User $user): ?int
```

### Question
```php
// Relationships
assessment(): BelongsTo
options(): HasMany
responses(): HasMany

// Type Checks
isMultipleChoice(): bool
isTrueFalse(): bool
isFillInBlank(): bool
isEssay(): bool
isMatching(): bool
isDragDrop(): bool
requiresManualGrading(): bool

// Operations
getCorrectOptions(): Collection
validateResponse($response): bool
calculateScore($response): float
```

### AssessmentAttempt
```php
// Relationships
assessment(): BelongsTo
user(): BelongsTo
gradedBy(): BelongsTo
responses(): HasMany

// Scopes
scopeCompleted(Builder $query): Builder
scopeInProgress(Builder $query): Builder
scopePassed(Builder $query): Builder
scopeFailed(Builder $query): Builder

// Status
isInProgress(): bool
isCompleted(): bool
isSubmitted(): bool
isGraded(): bool
hasPassed(): bool

// Time Management
isTimeLimitExceeded(): bool
getRemainingTime(): ?int

// Operations
submit(): void
grade(float $score, float $percentage, bool $passed, ?User $gradedBy): void
calculateScore(): float
calculatePercentage(): float
```

## Question Types

### Multiple Choice
```php
[
    'question_type' => 'multiple_choice',
    'options' => [
        ['option_text' => 'Option 1', 'is_correct' => true],
        ['option_text' => 'Option 2', 'is_correct' => false],
    ]
]
```

### True/False
```php
[
    'question_type' => 'true_false',
    'options' => [
        ['option_text' => 'True', 'is_correct' => true],
        ['option_text' => 'False', 'is_correct' => false],
    ]
]
```

### Essay
```php
[
    'question_type' => 'essay',
    // No options needed
]
```

### Fill in the Blank
```php
[
    'question_type' => 'fill_in_blank',
    // No options needed
]
```

## Common Patterns

### Creating a Complete Assessment
```php
// 1. Create assessment
$assessment = $assessmentService->createAssessment([
    'course_id' => $course->id,
    'title' => 'Chapter 1 Quiz',
    'passing_score' => 70,
    'time_limit' => 30,
], $instructor);

// 2. Add questions
$question = $questionService->createQuestion($assessment, [
    'question_text' => 'What is PHP?',
    'question_type' => 'multiple_choice',
    'points' => 1,
    'options' => [
        ['option_text' => 'Programming Language', 'is_correct' => true],
        ['option_text' => 'Database', 'is_correct' => false],
    ],
]);

// 3. Publish
$assessmentService->publishAssessment($assessment);
```

### Taking an Assessment
```php
// 1. Check eligibility
if ($assessmentService->canUserAttemptAssessment($assessment, $user)) {
    // 2. Start attempt
    $attempt = $attemptService->startAttempt($assessment, $user);
    
    // 3. Save responses
    foreach ($questions as $question) {
        $attemptService->saveResponse($attempt, $question, $userResponse);
    }
    
    // 4. Submit
    $attemptService->submitAttempt($attempt);
    
    // 5. Get results
    $results = $attemptService->getAttemptResults($attempt);
}
```

### Manual Grading
```php
// Get attempts needing grading
$attempts = $attemptService->getAttemptsRequiringGrading($assessment);

foreach ($attempts as $attempt) {
    foreach ($attempt->responses as $response) {
        if ($response->question->requiresManualGrading()) {
            $attemptService->manuallyGradeResponse(
                $response,
                $pointsEarned,
                $feedback,
                $instructor
            );
        }
    }
}
```

## Configuration Options

### Assessment Settings
- `passing_score`: 0-100 (percentage)
- `time_limit`: minutes (0 = unlimited)
- `max_attempts`: number (0 = unlimited)
- `randomize_questions`: boolean
- `randomize_options`: boolean
- `show_results`: boolean
- `show_correct_answers`: boolean
- `allow_review`: boolean
- `is_published`: boolean

### Question Settings
- `question_type`: multiple_choice, true_false, fill_in_blank, essay, matching, drag_drop
- `points`: decimal (0.5, 1, 2, etc.)
- `order_index`: integer
- `explanation`: text (shown after submission)

## Status Flow

### Attempt Status
1. `in_progress` - User is taking the assessment
2. `submitted` - Submitted, awaiting grading
3. `graded` - Fully graded, results available

### Grading Flow
1. User submits attempt
2. Objective questions auto-graded
3. If essay questions exist → status: `submitted`
4. Instructor grades essays
5. When all graded → status: `graded`
