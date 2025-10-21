# AI Service Foundation - Quick Reference

## Service Interface

```php
use App\Services\AI\Contracts\AIServiceInterface;

$aiService = app(AIServiceInterface::class);
```

## Core Methods

### Generate Text
```php
$response = $aiService->generateText(
    prompt: 'Your prompt here',
    options: [
        'model' => 'gpt-4',           // or 'gpt-3.5-turbo'
        'temperature' => 0.7,          // 0.0 to 2.0
        'max_tokens' => 2000           // max response length
    ]
);
```

### Analyze Text
```php
// Readability analysis
$analysis = $aiService->analyzeText($text, 'readability');
// Returns: ['reading_level', 'complexity_score', 'suggestions']

// Sentiment analysis
$analysis = $aiService->analyzeText($text, 'sentiment');
// Returns: ['sentiment', 'confidence', 'key_phrases']

// Summary
$analysis = $aiService->analyzeText($text, 'summary');
// Returns: ['summary', 'key_points', 'word_count']

// Keywords
$analysis = $aiService->analyzeText($text, 'keywords');
// Returns: ['keywords', 'topics']
```

### Generate Image
```php
$imageUrl = $aiService->generateImage('A beautiful sunset over mountains');
```

### Transcribe Audio
```php
$transcript = $aiService->transcribeAudio('/path/to/audio.mp3');
```

### Text to Speech
```php
$audioPath = $aiService->textToSpeech('Hello, welcome to the course');
// Returns: 'audio/tts_timestamp_uniqueid.mp3'
```

### Check Availability
```php
if ($aiService->isAvailable()) {
    // Service is available
}

$serviceName = $aiService->getName(); // 'OpenAI' or 'Fallback'
```

## Async Operations (Queue Jobs)

### Generate Text Async
```php
use App\Jobs\AI\GenerateAITextJob;

GenerateAITextJob::dispatch(
    prompt: 'Generate course description for Laravel basics',
    options: ['temperature' => 0.7],
    cacheKey: 'course_desc_' . $courseId,
    callbackClass: CourseService::class,
    callbackMethod: 'handleGeneratedDescription',
    callbackParams: [$courseId]
);
```

### Analyze Text Async
```php
use App\Jobs\AI\AnalyzeTextJob;

AnalyzeTextJob::dispatch(
    text: $courseContent,
    task: 'readability',
    cacheKey: 'readability_' . $courseId,
    callbackClass: ContentAnalyzerService::class,
    callbackMethod: 'handleAnalysisResult',
    callbackParams: [$courseId]
);
```

## Usage Statistics

```php
use App\Services\AI\OpenAIService;

$service = app(OpenAIService::class);

// Get today's stats
$stats = $service->getUsageStats();

// Get specific date stats
$stats = $service->getUsageStats('2024-01-15');

// Returns:
[
    'text-generation' => ['count' => 50, 'tokens' => 10000, 'cost' => 0.50],
    'text-analysis' => ['count' => 30, 'tokens' => 5000, 'cost' => 0.25],
    'image-generation' => ['count' => 5, 'tokens' => 0, 'cost' => 0.20],
    // ...
]
```

## Error Handling

```php
use App\Exceptions\AIServiceException;

try {
    $response = $aiService->generateText($prompt);
} catch (AIServiceException $e) {
    // Handle AI service error
    Log::error('AI service failed', ['error' => $e->getMessage()]);
    
    // Fallback logic
    $response = 'AI service temporarily unavailable';
}
```

## Configuration

### Environment Variables
```env
# Required
OPENAI_API_KEY=sk-...

# Optional
OPENAI_ORGANIZATION=org-...
OPENAI_MODEL=gpt-4
OPENAI_USE_FALLBACK=true
OPENAI_CACHE_ENABLED=true
OPENAI_CACHE_TTL=3600

# Rate Limits (per minute)
OPENAI_RATE_LIMIT_TEXT=60
OPENAI_RATE_LIMIT_ANALYSIS=60
OPENAI_RATE_LIMIT_IMAGE=10
OPENAI_RATE_LIMIT_AUDIO=30
OPENAI_RATE_LIMIT_TTS=30
```

### Service Configuration
```php
// config/services.php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'rate_limits' => [...],
    'costs' => [...],
    'use_fallback' => true,
    'cache_enabled' => true,
    'cache_ttl' => 3600,
]
```

## Service Resolution

```php
// Get default AI service (OpenAI or Fallback)
$service = app(AIServiceInterface::class);

// Get OpenAI service explicitly
$openai = app(OpenAIService::class);

// Get Fallback service explicitly
$fallback = app(FallbackAIService::class);
```

## Caching

Responses are automatically cached when `cache_enabled` is true:

```php
// First call - hits API
$response1 = $aiService->generateText('What is Laravel?');

// Second call - returns cached response
$response2 = $aiService->generateText('What is Laravel?');
```

Cache keys are generated from prompt/text content. Custom cache keys can be provided in queue jobs.

## Rate Limiting

Rate limits are enforced per operation type:

```php
// If rate limit exceeded
try {
    $response = $aiService->generateText($prompt);
} catch (AIServiceException $e) {
    // "Rate limit exceeded for text-generation. Limit: 60 per minute."
}
```

## Fallback Service

When OpenAI is unavailable, the fallback service provides basic functionality:

- **Text Generation**: Returns generic message
- **Readability Analysis**: Basic word/sentence counting
- **Sentiment Analysis**: Simple keyword matching
- **Summary**: First 3 sentences
- **Keywords**: Word frequency analysis
- **Image/Audio**: Throws exception (not supported)

## Testing

### Mock AI Responses
```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'api.openai.com/*' => Http::response([
        'choices' => [
            ['message' => ['content' => 'Mocked response']]
        ],
        'usage' => ['total_tokens' => 30]
    ], 200)
]);

$service = new OpenAIService();
$result = $service->generateText('Test');
// Returns: 'Mocked response'
```

### Use Fallback in Tests
```php
$this->app->singleton(AIServiceInterface::class, function () {
    return new FallbackAIService();
});

$service = app(AIServiceInterface::class);
// Uses fallback service
```

## Common Patterns

### Generate with Retry
```php
$maxRetries = 3;
$attempt = 0;

while ($attempt < $maxRetries) {
    try {
        $response = $aiService->generateText($prompt);
        break;
    } catch (AIServiceException $e) {
        $attempt++;
        if ($attempt >= $maxRetries) {
            throw $e;
        }
        sleep(pow(2, $attempt)); // Exponential backoff
    }
}
```

### Batch Processing
```php
foreach ($courses as $course) {
    GenerateAITextJob::dispatch(
        prompt: "Generate description for: {$course->title}",
        cacheKey: "course_desc_{$course->id}",
        callbackClass: CourseService::class,
        callbackMethod: 'updateDescription',
        callbackParams: [$course->id]
    )->onQueue('ai-processing');
}
```

### Cost Monitoring
```php
$stats = $service->getUsageStats();
$totalCost = array_sum(array_column($stats, 'cost'));

if ($totalCost > 100) {
    // Alert: Daily AI cost exceeded $100
    Notification::send($admins, new HighAICostAlert($totalCost));
}
```
