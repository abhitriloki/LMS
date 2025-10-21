# AI Question Generator - Quick Reference

## Quick Start

### Generate Questions from a Lesson

1. Navigate to lesson edit page
2. Click "Generate Questions with AI"
3. Configure parameters:
   - Number of questions (1-20)
   - Question types
   - Difficulty level
   - Points per question
4. Click "Generate Questions"
5. Wait for processing (check status page)
6. Review generated questions
7. Approve, reject, or edit questions

## API Endpoints

### Generate Questions
```http
POST /admin/questions/generator/generate
Content-Type: application/json

{
  "lesson_id": 1,
  "count": 5,
  "types": ["multiple_choice", "true_false"],
  "difficulty": "medium",
  "include_explanation": true,
  "points_per_question": 1
}
```

### Approve Question
```http
POST /admin/questions/generator/{generatedQuestion}/approve
Content-Type: application/json

{
  "assessment_id": 1,
  "review_notes": "Looks good"
}
```

### Reject Question
```http
POST /admin/questions/generator/{generatedQuestion}/reject
Content-Type: application/json

{
  "review_notes": "Not accurate"
}
```

### Bulk Approve
```http
POST /admin/questions/generator/bulk-approve
Content-Type: application/json

{
  "question_ids": [1, 2, 3],
  "assessment_id": 1
}
```

## Service Usage

### Generate Questions Programmatically

```php
use App\Services\AI\AIQuestionGeneratorService;
use App\Models\CourseLesson;

$service = app(AIQuestionGeneratorService::class);
$lesson = CourseLesson::find(1);

$parameters = [
    'count' => 5,
    'types' => ['multiple_choice', 'true_false'],
    'difficulty' => 'medium',
    'include_explanation' => true,
    'points_per_question' => 1,
];

$questions = $service->generateQuestionsFromLesson($lesson, $parameters);
```

### Extract Content Text

```php
$service = app(AIQuestionGeneratorService::class);
$lesson = CourseLesson::find(1);

$text = $service->extractContentText($lesson);
```

### Save Generated Questions

```php
$job = AIQuestionJob::find(1);
$questions = [...]; // Array of formatted questions

$count = $service->saveGeneratedQuestions($job, $questions);
```

## Job Dispatching

### Dispatch Generation Job

```php
use App\Jobs\GenerateQuestionsJob;
use App\Models\AIQuestionJob;

$job = AIQuestionJob::create([
    'lesson_id' => $lessonId,
    'requested_by' => auth()->id(),
    'status' => 'pending',
    'parameters' => $parameters,
]);

GenerateQuestionsJob::dispatch($job);
```

### Check Job Status

```php
$job = AIQuestionJob::find(1);

if ($job->isPending()) {
    // Still waiting
}

if ($job->isProcessing()) {
    // Currently processing
}

if ($job->isCompleted()) {
    // Done - review questions
    $questions = $job->generatedQuestions()->pending()->get();
}

if ($job->hasFailed()) {
    // Failed - check error message
    $error = $job->error_message;
}
```

## Model Operations

### AIQuestionJob

```php
// Create job
$job = AIQuestionJob::create([
    'lesson_id' => 1,
    'requested_by' => auth()->id(),
    'status' => 'pending',
    'parameters' => [...],
]);

// Update status
$job->markAsProcessing();
$job->markAsCompleted($questionsCount);
$job->markAsFailed($errorMessage);

// Query scopes
$pending = AIQuestionJob::pending()->get();
$processing = AIQuestionJob::processing()->get();
$completed = AIQuestionJob::completed()->get();
$failed = AIQuestionJob::failed()->get();
```

### GeneratedQuestion

```php
// Create question
$question = GeneratedQuestion::create([
    'job_id' => $jobId,
    'question_text' => 'What is Laravel?',
    'question_type' => 'multiple_choice',
    'options' => ['Framework', 'Database', 'Server', 'Language'],
    'correct_answer' => [0],
    'explanation' => 'Laravel is a PHP framework',
    'points' => 1,
    'status' => 'pending',
]);

// Approve/Reject
$question->approve($reviewer, 'Looks good');
$question->reject($reviewer, 'Not accurate');

// Link to final question
$question->linkToQuestion($finalQuestion);

// Query scopes
$pending = GeneratedQuestion::pending()->get();
$approved = GeneratedQuestion::approved()->get();
$rejected = GeneratedQuestion::rejected()->get();
```

## Supported Question Types

### Multiple Choice
```php
[
    'question_text' => 'What is 2+2?',
    'question_type' => 'multiple_choice',
    'options' => ['3', '4', '5', '6'],
    'correct_answer' => [1], // Index of correct option
    'explanation' => '2+2 equals 4',
    'points' => 1,
]
```

### True/False
```php
[
    'question_text' => 'Laravel is a PHP framework.',
    'question_type' => 'true_false',
    'options' => ['True', 'False'],
    'correct_answer' => [0], // 0 for True, 1 for False
    'explanation' => 'Yes, Laravel is a PHP framework',
    'points' => 1,
]
```

### Fill in the Blank
```php
[
    'question_text' => 'Laravel uses the [BLANK] pattern.',
    'question_type' => 'fill_in_blank',
    'options' => null,
    'correct_answer' => ['MVC'], // Array of acceptable answers
    'explanation' => 'Laravel follows MVC pattern',
    'points' => 1,
]
```

### Essay
```php
[
    'question_text' => 'Explain the benefits of Laravel.',
    'question_type' => 'essay',
    'options' => null,
    'correct_answer' => null,
    'explanation' => 'Look for mentions of MVC, Eloquent, etc.',
    'points' => 5,
]
```

## Content Extraction

### Supported Content Types

- **Text:** Direct content from `content_path`
- **PDF:** Extracted using `smalot/pdfparser`
- **Video:** From metadata transcription or transcription file

### PDF Extraction
```php
// Automatic extraction
$text = $service->extractContentText($pdfLesson);

// Manual extraction
use Smalot\PdfParser\Parser;

$parser = new Parser();
$pdf = $parser->parseFile($filePath);
$text = $pdf->getText();
```

### Video Transcription
```php
// Store transcription in metadata
$lesson->update([
    'metadata' => [
        'transcription' => 'Video transcript text...',
    ],
]);

// Or store in separate file
$lesson->update([
    'metadata' => [
        'transcription_path' => 'transcriptions/lesson-1.txt',
    ],
]);
```

## Validation Rules

### Generation Request
```php
[
    'lesson_id' => 'required|exists:course_lessons,id',
    'count' => 'required|integer|min:1|max:20',
    'types' => 'required|array|min:1',
    'types.*' => 'string|in:multiple_choice,true_false,fill_in_blank,essay',
    'difficulty' => 'required|string|in:easy,medium,hard',
    'include_explanation' => 'boolean',
    'points_per_question' => 'required|numeric|min:0.5|max:10',
]
```

### Question Update
```php
[
    'question_text' => 'required|string',
    'question_type' => 'required|string|in:multiple_choice,true_false,fill_in_blank,essay',
    'options' => 'nullable|array',
    'correct_answer' => 'nullable',
    'explanation' => 'nullable|string',
    'points' => 'required|numeric|min:0.5|max:10',
]
```

## Notifications

### Listen for Notifications
```php
// In User model or notification listener
public function receivesBroadcastNotificationsOn()
{
    return 'users.' . $this->id;
}

// Frontend (Alpine.js)
Echo.private(`users.${userId}`)
    .notification((notification) => {
        if (notification.type === 'question_generation_completed') {
            // Show success message
            window.location.href = notification.action_url;
        }
    });
```

## Testing

### Mock AI Service
```php
use App\Services\AI\Contracts\AIServiceInterface;
use Mockery;

$mockAI = Mockery::mock(AIServiceInterface::class);
$mockAI->shouldReceive('generateText')
    ->once()
    ->andReturn(json_encode([...]));

$this->app->instance(AIServiceInterface::class, $mockAI);
```

### Test Generation
```php
public function test_generates_questions()
{
    $lesson = CourseLesson::factory()->create([
        'content_type' => 'text',
        'content_path' => 'Test content',
    ]);

    $response = $this->actingAs($instructor)
        ->post(route('admin.questions.generator.generate'), [
            'lesson_id' => $lesson->id,
            'count' => 5,
            'types' => ['multiple_choice'],
            'difficulty' => 'medium',
            'points_per_question' => 1,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ai_question_jobs', [
        'lesson_id' => $lesson->id,
    ]);
}
```

## Common Issues

### No Content Extracted
- Ensure lesson has content in `content_path`
- Check PDF file exists and is readable
- Verify video has transcription in metadata

### Generation Fails
- Check AI service configuration
- Verify API key is valid
- Check rate limits
- Review error logs

### Questions Not Appearing
- Verify job status is 'completed'
- Check questions have 'pending' status
- Ensure user has permission to view

## Performance Tips

1. **Batch Processing:** Generate questions for multiple lessons in sequence
2. **Queue Configuration:** Use dedicated queue for AI processing
3. **Caching:** AI responses are cached by the AI service layer
4. **Rate Limiting:** Respect AI service rate limits
5. **Timeout:** Increase timeout for large content (default: 5 minutes)

## Related Files

- Service: `app/Services/AI/AIQuestionGeneratorService.php`
- Controller: `app/Http/Controllers/Admin/QuestionGeneratorController.php`
- Job: `app/Jobs/GenerateQuestionsJob.php`
- Models: `app/Models/AIQuestionJob.php`, `app/Models/GeneratedQuestion.php`
- Views: `resources/views/admin/questions/generate.blade.php`
- Tests: `tests/Feature/AI/QuestionGeneratorTest.php`
