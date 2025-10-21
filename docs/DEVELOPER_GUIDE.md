# Developer Guide

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Technology Stack](#technology-stack)
3. [Code Organization](#code-organization)
4. [Development Patterns](#development-patterns)
5. [API Development](#api-development)
6. [Database Management](#database-management)
7. [Testing](#testing)
8. [AI Service Integration](#ai-service-integration)
9. [Frontend Development](#frontend-development)
10. [Performance Optimization](#performance-optimization)
11. [Security Guidelines](#security-guidelines)
12. [Deployment](#deployment)

## Architecture Overview

The Corporate LMS follows a monolithic architecture built on Laravel 11 with clear separation of concerns:

### Architectural Layers

```
┌─────────────────────────────────────────┐
│         Presentation Layer              │
│  (Blade Views, Alpine.js, Livewire)     │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         Application Layer               │
│  (Controllers, Middleware, Requests)    │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│          Service Layer                  │
│  (Business Logic, AI Services)          │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         Repository Layer                │
│  (Data Access, Eloquent)                │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         Infrastructure Layer            │
│  (Database, Cache, Queue, Storage)      │
└─────────────────────────────────────────┘
```

### Design Patterns

- **Repository Pattern**: Data access abstraction
- **Service Pattern**: Business logic encapsulation
- **Observer Pattern**: Event-driven architecture
- **Factory Pattern**: Object creation
- **Strategy Pattern**: AI service implementations


## Technology Stack

### Backend

- **Framework**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL 8.0+
- **Cache**: Redis 7.0+
- **Queue**: Redis with Laravel Horizon
- **Search**: Meilisearch with Laravel Scout
- **Authentication**: Laravel Sanctum
- **Real-time**: Laravel WebSockets / Pusher

### Frontend

- **Templating**: Laravel Blade
- **JavaScript**: Alpine.js 3.x
- **CSS**: Tailwind CSS 3.x
- **Dynamic Components**: Livewire 3.x
- **Charts**: Chart.js
- **Video Player**: Video.js
- **File Upload**: Dropzone.js

### AI Services

- **Primary**: OpenAI API (GPT-4, DALL-E 3, Whisper, TTS)
- **Fallback**: Google Gemini (configurable)

### Development Tools

- **Package Manager**: Composer, NPM
- **Build Tool**: Vite
- **Testing**: PHPUnit, Pest
- **Code Quality**: PHP CS Fixer, PHPStan
- **API Documentation**: OpenAPI/Swagger


## Code Organization

### Directory Structure

```
app/
├── Console/Commands/          # Artisan commands
├── Events/                    # Domain events
├── Exceptions/                # Custom exceptions
├── Http/
│   ├── Controllers/           # Request handlers
│   │   ├── Admin/            # Admin controllers
│   │   ├── Api/              # API controllers
│   │   ├── Instructor/       # Instructor controllers
│   │   └── Student/          # Student controllers
│   ├── Middleware/           # HTTP middleware
│   ├── Requests/             # Form request validation
│   └── Resources/            # API resources
├── Jobs/                     # Queue jobs
├── Listeners/                # Event listeners
├── Models/                   # Eloquent models
├── Notifications/            # Notification classes
├── Observers/                # Model observers
├── Policies/                 # Authorization policies
├── Providers/                # Service providers
├── Repositories/             # Data access layer
│   ├── Contracts/           # Repository interfaces
│   └── Eloquent/            # Eloquent implementations
├── Rules/                    # Custom validation rules
├── Services/                 # Business logic
│   ├── AI/                  # AI-related services
│   ├── Course/              # Course management
│   ├── Assessment/          # Assessment services
│   └── ...
├── Traits/                   # Reusable traits
└── View/Components/          # Blade components
```

### Naming Conventions

**Controllers:**
- Singular resource name: `CourseController`
- RESTful methods: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`

**Models:**
- Singular, PascalCase: `Course`, `Enrollment`, `Assessment`
- Table names: plural, snake_case: `courses`, `enrollments`, `assessments`

**Services:**
- Descriptive name + Service: `CourseService`, `EnrollmentService`
- Interface suffix: `CourseServiceInterface`

**Repositories:**
- Resource + Repository: `CourseRepository`
- Interface: `CourseRepositoryInterface`
- Implementation: `EloquentCourseRepository`

**Jobs:**
- Verb + noun: `ProcessVideoJob`, `GenerateRecommendationsJob`

**Events:**
- Past tense: `CoursePublished`, `AssessmentCompleted`

**Listeners:**
- Action description: `SendCoursePublishedNotification`


## Development Patterns

### Repository Pattern

Repositories abstract data access logic:

```php
// Interface
interface CourseRepositoryInterface
{
    public function create(array $data): Course;
    public function findById(int $id): ?Course;
    public function update(Course $course, array $data): Course;
    public function delete(Course $course): bool;
    public function search(array $filters): LengthAwarePaginator;
}

// Implementation
class EloquentCourseRepository implements CourseRepositoryInterface
{
    public function create(array $data): Course
    {
        return Course::create($data);
    }
    
    public function findById(int $id): ?Course
    {
        return Course::with(['modules.lessons', 'category'])
            ->find($id);
    }
    
    // ... other methods
}

// Binding in Service Provider
$this->app->bind(
    CourseRepositoryInterface::class,
    EloquentCourseRepository::class
);
```

### Service Pattern

Services encapsulate business logic:

```php
class CourseService
{
    public function __construct(
        private CourseRepositoryInterface $courseRepository,
        private ModuleService $moduleService,
        private CacheInvalidationService $cacheService
    ) {}
    
    public function createCourse(array $data, User $creator): Course
    {
        DB::beginTransaction();
        
        try {
            $course = $this->courseRepository->create([
                ...$data,
                'created_by' => $creator->id,
                'slug' => Str::slug($data['title']),
            ]);
            
            // Clear relevant caches
            $this->cacheService->invalidateCourseCache();
            
            // Dispatch events
            event(new CourseCreated($course));
            
            DB::commit();
            return $course;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new CourseCreationException($e->getMessage());
        }
    }
}
```


### Event-Driven Architecture

Use events for decoupled functionality:

```php
// Event
class CourseCompleted
{
    public function __construct(
        public Enrollment $enrollment
    ) {}
}

// Listener
class GenerateCertificate
{
    public function handle(CourseCompleted $event): void
    {
        GenerateCertificateJob::dispatch($event->enrollment);
    }
}

// Register in EventServiceProvider
protected $listen = [
    CourseCompleted::class => [
        GenerateCertificate::class,
        UpdateUserProgress::class,
        SendCompletionNotification::class,
    ],
];

// Dispatch event
event(new CourseCompleted($enrollment));
```

### Queue Jobs

Use queues for long-running tasks:

```php
class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $timeout = 600;
    
    public function __construct(
        public Lesson $lesson,
        public string $videoPath
    ) {}
    
    public function handle(VideoProcessingService $service): void
    {
        $service->processVideo($this->lesson, $this->videoPath);
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error('Video processing failed', [
            'lesson_id' => $this->lesson->id,
            'error' => $exception->getMessage()
        ]);
    }
}

// Dispatch job
ProcessVideoJob::dispatch($lesson, $videoPath)
    ->onQueue('video-processing');
```


## API Development

### RESTful API Structure

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Courses
    Route::apiResource('courses', CourseController::class);
    Route::get('courses/{course}/modules', [CourseController::class, 'modules']);
    
    // Enrollments
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::get('enrollments', [EnrollmentController::class, 'index']);
    
    // Assessments
    Route::apiResource('assessments', AssessmentController::class);
    Route::post('assessments/{assessment}/start', [AttemptController::class, 'start']);
});
```

### API Controllers

```php
class Api\CourseController extends Controller
{
    use ApiResponse; // Trait for consistent responses
    
    public function __construct(
        private CourseService $courseService
    ) {}
    
    public function index(Request $request): JsonResponse
    {
        $courses = $this->courseService->search([
            'search' => $request->input('search'),
            'category' => $request->input('category'),
            'difficulty' => $request->input('difficulty'),
            'per_page' => $request->input('per_page', 15),
        ]);
        
        return $this->successResponse(
            CourseResource::collection($courses),
            'Courses retrieved successfully'
        );
    }
    
    public function show(Course $course): JsonResponse
    {
        $this->authorize('view', $course);
        
        return $this->successResponse(
            new CourseResource($course->load(['modules.lessons', 'category'])),
            'Course retrieved successfully'
        );
    }
    
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->createCourse(
            $request->validated(),
            $request->user()
        );
        
        return $this->successResponse(
            new CourseResource($course),
            'Course created successfully',
            201
        );
    }
}
```

### API Resources

```php
class CourseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'difficulty_level' => $this->difficulty_level,
            'estimated_duration' => $this->estimated_duration,
            'thumbnail' => $this->thumbnail_url,
            'is_published' => $this->is_published,
            'enrollments_count' => $this->when(
                $request->user()->isAdmin(),
                $this->enrollments_count
            ),
            'modules' => ModuleResource::collection($this->whenLoaded('modules')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

### API Response Trait

```php
trait ApiResponse
{
    protected function successResponse($data, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
    
    protected function errorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
```


## Database Management

### Migrations

Create migrations for database changes:

```php
// Create migration
php artisan make:migration create_courses_table

// Migration file
public function up(): void
{
    Schema::create('courses', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->text('description');
        $table->foreignId('category_id')->constrained()->cascadeOnDelete();
        $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced']);
        $table->integer('estimated_duration');
        $table->boolean('is_published')->default(false);
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
        $table->softDeletes();
        
        // Indexes
        $table->index('slug');
        $table->index('is_published');
        $table->index('created_at');
    });
}
```

### Model Relationships

```php
class Course extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'title', 'slug', 'description', 'category_id',
        'difficulty_level', 'estimated_duration', 'is_published'
    ];
    
    protected $casts = [
        'is_published' => 'boolean',
        'prerequisites' => 'array',
        'learning_objectives' => 'array',
    ];
    
    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order_index');
    }
    
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
    
    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
    
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    // Accessors
    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail 
            ? Storage::url($this->thumbnail)
            : asset('images/default-course.jpg');
    }
}
```

### Query Optimization

```php
// Eager loading to prevent N+1 queries
$courses = Course::with(['category', 'modules.lessons'])
    ->published()
    ->paginate(15);

// Select specific columns
$courses = Course::select(['id', 'title', 'slug', 'thumbnail'])
    ->published()
    ->get();

// Chunk for large datasets
Course::chunk(100, function ($courses) {
    foreach ($courses as $course) {
        // Process course
    }
});

// Use indexes
$courses = Course::where('is_published', true) // indexed
    ->where('category_id', $categoryId)        // indexed
    ->orderBy('created_at', 'desc')            // indexed
    ->get();
```


## Testing

### Unit Tests

Test individual components:

```php
// tests/Unit/Services/CourseServiceTest.php
class CourseServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private CourseService $service;
    private CourseRepositoryInterface $repository;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(CourseRepositoryInterface::class);
        $this->service = new CourseService($this->repository);
    }
    
    public function test_create_course_with_valid_data(): void
    {
        $user = User::factory()->create();
        $data = [
            'title' => 'Test Course',
            'description' => 'Test Description',
            'category_id' => 1,
        ];
        
        $this->repository
            ->shouldReceive('create')
            ->once()
            ->andReturn(new Course($data));
        
        $course = $this->service->createCourse($data, $user);
        
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals('Test Course', $course->title);
    }
}
```

### Feature Tests

Test complete workflows:

```php
// tests/Feature/CourseEnrollmentTest.php
class CourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_enroll_in_published_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        
        $response = $this->actingAs($user)
            ->post("/courses/{$course->id}/enroll");
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
    }
    
    public function test_user_cannot_enroll_in_unpublished_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['is_published' => false]);
        
        $response = $this->actingAs($user)
            ->post("/courses/{$course->id}/enroll");
        
        $response->assertForbidden();
    }
}
```

### API Tests

```php
// tests/Feature/Api/CourseApiTest.php
class CourseApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_list_courses(): void
    {
        $user = User::factory()->create();
        Course::factory()->count(5)->published()->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/courses');
        
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'slug', 'description']
                ]
            ]);
    }
}
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/CourseTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter test_user_can_enroll_in_course
```


## AI Service Integration

### AI Service Interface

```php
interface AIServiceInterface
{
    public function generateText(string $prompt, array $options = []): string;
    public function analyzeText(string $text, string $task): array;
    public function generateImage(string $prompt): string;
    public function transcribeAudio(string $audioPath): string;
}
```

### OpenAI Service Implementation

```php
class OpenAIService implements AIServiceInterface
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }
    
    public function generateText(string $prompt, array $options = []): string
    {
        try {
            $response = $this->client->chat()->create([
                'model' => $options['model'] ?? 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $options['system'] ?? ''],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
            ]);
            
            return $response->choices[0]->message->content;
            
        } catch (RateLimitException $e) {
            throw new AIServiceException('Rate limit exceeded');
        } catch (ApiException $e) {
            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
            throw new AIServiceException('AI service unavailable');
        }
    }
}
```

### Using AI Services

```php
class AIRecommendationService
{
    public function __construct(
        private AIServiceInterface $aiService,
        private UserRepositoryInterface $userRepository
    ) {}
    
    public function generateRecommendations(User $user): array
    {
        $userProfile = $this->buildUserProfile($user);
        
        $prompt = "Based on this user profile, recommend 5 relevant courses:\n\n"
            . json_encode($userProfile, JSON_PRETTY_PRINT);
        
        $response = $this->aiService->generateText($prompt, [
            'system' => 'You are a learning recommendation expert.',
            'temperature' => 0.8,
        ]);
        
        return $this->parseRecommendations($response);
    }
    
    private function buildUserProfile(User $user): array
    {
        return [
            'role' => $user->position,
            'department' => $user->department->name,
            'completed_courses' => $user->completedCourses->pluck('title'),
            'skills' => $user->skills,
            'interests' => $user->interests,
        ];
    }
}
```

### Mocking AI Services in Tests

```php
class AIRecommendationServiceTest extends TestCase
{
    public function test_generates_recommendations(): void
    {
        $mockAI = Mockery::mock(AIServiceInterface::class);
        $mockAI->shouldReceive('generateText')
            ->once()
            ->andReturn(json_encode([
                ['course_id' => 1, 'score' => 0.95, 'reason' => 'Test'],
            ]));
        
        $service = new AIRecommendationService($mockAI, $this->userRepository);
        $recommendations = $service->generateRecommendations($user);
        
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
    }
}
```


## Frontend Development

### Blade Components

Create reusable Blade components:

```php
// app/View/Components/CourseCard.php
class CourseCard extends Component
{
    public function __construct(
        public Course $course,
        public bool $showActions = true
    ) {}
    
    public function render(): View
    {
        return view('components.course-card');
    }
}
```

```blade
{{-- resources/views/components/course-card.blade.php --}}
<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
    <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" class="w-full h-48 object-cover">
    
    <div class="p-4">
        <h3 class="text-lg font-semibold mb-2">{{ $course->title }}</h3>
        <p class="text-gray-600 text-sm mb-4">{{ Str::limit($course->description, 100) }}</p>
        
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">{{ $course->estimated_duration }} hours</span>
            
            @if($showActions)
                <a href="{{ route('courses.show', $course) }}" class="btn-primary">
                    View Course
                </a>
            @endif
        </div>
    </div>
</div>
```

### Alpine.js Components

```blade
{{-- Video Player with Alpine.js --}}
<div x-data="videoPlayer()" x-init="init()">
    <video 
        x-ref="video"
        @timeupdate="updateProgress"
        @ended="markComplete"
        class="w-full"
    >
        <source :src="videoUrl" type="video/mp4">
    </video>
    
    <div class="controls">
        <button @click="togglePlay" x-text="playing ? 'Pause' : 'Play'"></button>
        <input 
            type="range" 
            :value="progress" 
            @input="seek($event.target.value)"
            min="0" 
            max="100"
        >
        <span x-text="formatTime(currentTime)"></span>
    </div>
</div>

<script>
function videoPlayer() {
    return {
        playing: false,
        progress: 0,
        currentTime: 0,
        videoUrl: '{{ $lesson->video_url }}',
        
        init() {
            this.restoreBookmark();
        },
        
        togglePlay() {
            if (this.playing) {
                this.$refs.video.pause();
            } else {
                this.$refs.video.play();
            }
            this.playing = !this.playing;
        },
        
        updateProgress() {
            const video = this.$refs.video;
            this.progress = (video.currentTime / video.duration) * 100;
            this.currentTime = video.currentTime;
            this.saveBookmark();
        },
        
        saveBookmark() {
            fetch('/api/lessons/{{ $lesson->id }}/bookmark', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ position: this.currentTime })
            });
        }
    }
}
</script>
```

### Livewire Components

```php
// app/Http/Livewire/CourseSearch.php
class CourseSearch extends Component
{
    public string $search = '';
    public string $category = '';
    public string $difficulty = '';
    
    protected $queryString = ['search', 'category', 'difficulty'];
    
    public function render()
    {
        $courses = Course::query()
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->category, fn($q) => $q->where('category_id', $this->category))
            ->when($this->difficulty, fn($q) => $q->where('difficulty_level', $this->difficulty))
            ->published()
            ->paginate(12);
        
        return view('livewire.course-search', compact('courses'));
    }
}
```

```blade
{{-- resources/views/livewire/course-search.blade.php --}}
<div>
    <div class="mb-6 flex gap-4">
        <input 
            type="text" 
            wire:model.debounce.300ms="search" 
            placeholder="Search courses..."
            class="flex-1 px-4 py-2 border rounded"
        >
        
        <select wire:model="category" class="px-4 py-2 border rounded">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        
        <select wire:model="difficulty" class="px-4 py-2 border rounded">
            <option value="">All Levels</option>
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
        </select>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($courses as $course)
            <x-course-card :course="$course" />
        @endforeach
    </div>
    
    <div class="mt-6">
        {{ $courses->links() }}
    </div>
</div>
```


## Performance Optimization

### Caching Strategies

```php
// Cache course catalog
public function getCourses(): Collection
{
    return Cache::remember('courses.published', 3600, function () {
        return Course::with(['category', 'creator'])
            ->published()
            ->get();
    });
}

// Cache with tags
Cache::tags(['courses', 'catalog'])->put('featured-courses', $courses, 3600);

// Invalidate cache
Cache::tags(['courses'])->flush();

// Cache user-specific data
$userProgress = Cache::remember(
    "user.{$userId}.progress",
    1800,
    fn() => $this->calculateUserProgress($userId)
);
```

### Query Optimization

```php
// Use eager loading
$courses = Course::with([
    'category',
    'modules' => fn($q) => $q->orderBy('order_index'),
    'modules.lessons' => fn($q) => $q->orderBy('order_index'),
])->get();

// Use select to limit columns
$courses = Course::select(['id', 'title', 'slug', 'thumbnail'])
    ->published()
    ->get();

// Use exists for checking relationships
$hasEnrollments = $course->enrollments()->exists();

// Use count for counting
$enrollmentCount = $course->enrollments()->count();

// Avoid N+1 with withCount
$courses = Course::withCount('enrollments')->get();
```

### Database Indexing

```php
// Add indexes in migrations
Schema::table('courses', function (Blueprint $table) {
    $table->index('is_published');
    $table->index('category_id');
    $table->index('created_at');
    $table->index(['is_published', 'category_id']);
});

// Composite index for common queries
$table->index(['user_id', 'course_id', 'status'], 'enrollment_lookup');
```

### Queue Optimization

```php
// Use job batching
Bus::batch([
    new ProcessVideoJob($lesson1, $video1),
    new ProcessVideoJob($lesson2, $video2),
    new ProcessVideoJob($lesson3, $video3),
])->then(function (Batch $batch) {
    // All jobs completed
})->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure
})->dispatch();

// Job prioritization
ProcessVideoJob::dispatch($lesson, $video)
    ->onQueue('high-priority');

// Delayed jobs
GenerateRecommendationsJob::dispatch($user)
    ->delay(now()->addMinutes(5));
```

### Asset Optimization

```bash
# Compile and minify assets
npm run build

# Optimize images
php artisan optimize:images

# Generate asset manifest
php artisan optimize
```


## Security Guidelines

### Input Validation

```php
// Form Request Validation
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Course::class);
    }
    
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],
            'difficulty_level' => ['required', 'in:beginner,intermediate,advanced'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'Course title is required',
            'category_id.exists' => 'Selected category does not exist',
        ];
    }
}
```

### Authorization

```php
// Policy
class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        return $course->is_published || $user->id === $course->created_by;
    }
    
    public function update(User $user, Course $course): bool
    {
        return $user->id === $course->created_by || $user->isAdmin();
    }
    
    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }
}

// Usage in controller
public function update(UpdateCourseRequest $request, Course $course)
{
    $this->authorize('update', $course);
    
    $course = $this->courseService->updateCourse($course, $request->validated());
    
    return redirect()->route('courses.show', $course);
}
```

### SQL Injection Prevention

```php
// Always use parameter binding (Eloquent does this automatically)
$courses = Course::where('category_id', $categoryId)->get(); // Safe

// Never concatenate user input
$courses = DB::select("SELECT * FROM courses WHERE category_id = " . $categoryId); // Unsafe!

// Use bindings for raw queries
$courses = DB::select('SELECT * FROM courses WHERE category_id = ?', [$categoryId]); // Safe
```

### XSS Prevention

```blade
{{-- Blade automatically escapes output --}}
<h1>{{ $course->title }}</h1> {{-- Safe --}}

{{-- Use {!! !!} only for trusted HTML --}}
<div>{!! $course->description !!}</div> {{-- Only if sanitized --}}

{{-- Sanitize user HTML input --}}
$clean = Purifier::clean($request->input('content'));
```

### CSRF Protection

```blade
{{-- CSRF token automatically included in forms --}}
<form method="POST" action="{{ route('courses.store') }}">
    @csrf
    {{-- form fields --}}
</form>

{{-- For AJAX requests --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
axios.defaults.headers.common['X-CSRF-TOKEN'] = 
    document.querySelector('meta[name="csrf-token"]').content;
</script>
```

### Rate Limiting

```php
// In routes
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/api/courses', [CourseController::class, 'store']);
});

// Custom rate limiting
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// In controller
public function store(Request $request)
{
    RateLimiter::attempt(
        'create-course:' . $request->user()->id,
        $perMinute = 5,
        function() use ($request) {
            // Create course
        }
    );
}
```

### Secure File Uploads

```php
public function uploadFile(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
    ]);
    
    $file = $request->file('file');
    
    // Generate unique filename
    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
    
    // Store in private directory
    $path = $file->storeAs('uploads/private', $filename, 'private');
    
    // Scan for viruses (if ClamAV is available)
    if (config('security.virus_scan')) {
        $this->scanFile($path);
    }
    
    return $path;
}
```


## Deployment

### Environment Configuration

```bash
# Production .env settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lms.yourcompany.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=lms_production
DB_USERNAME=lms_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=your-redis-host
REDIS_PASSWORD=redis_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourcompany.com

OPENAI_API_KEY=your_openai_key

AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=lms-storage
```

### Deployment Steps

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# 3. Build assets
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Optimize application
php artisan optimize

# 7. Restart queue workers
php artisan queue:restart

# 8. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### Zero-Downtime Deployment

```bash
# Use Laravel Envoy or Deployer
# Example with Envoy

@servers(['web' => 'user@server.com'])

@task('deploy', ['on' => 'web'])
    cd /var/www/lms
    
    # Enable maintenance mode
    php artisan down --retry=60
    
    # Pull changes
    git pull origin main
    
    # Install dependencies
    composer install --no-dev --optimize-autoloader
    npm ci --production
    npm run build
    
    # Run migrations
    php artisan migrate --force
    
    # Clear caches
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Restart services
    php artisan queue:restart
    sudo systemctl reload php8.2-fpm
    
    # Disable maintenance mode
    php artisan up
@endtask
```

### Health Checks

```php
// routes/web.php
Route::get('/health', [HealthCheckController::class, 'check']);

// HealthCheckController
public function check(): JsonResponse
{
    $checks = [
        'database' => $this->checkDatabase(),
        'redis' => $this->checkRedis(),
        'storage' => $this->checkStorage(),
        'queue' => $this->checkQueue(),
    ];
    
    $healthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');
    
    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toISOString(),
    ], $healthy ? 200 : 503);
}
```

### Monitoring

```php
// Log important events
Log::channel('slack')->info('Deployment completed', [
    'version' => config('app.version'),
    'deployed_by' => $user->name,
]);

// Monitor queue health
php artisan queue:monitor redis:default,redis:high-priority --max=100

// Monitor failed jobs
php artisan queue:failed

// Retry failed jobs
php artisan queue:retry all
```


## Common Development Tasks

### Creating a New Feature

1. **Create Migration**
```bash
php artisan make:migration create_feature_table
php artisan migrate
```

2. **Create Model**
```bash
php artisan make:model Feature -mfsc
# -m: migration, -f: factory, -s: seeder, -c: controller
```

3. **Create Repository**
```bash
php artisan make:interface Repositories/Contracts/FeatureRepositoryInterface
php artisan make:class Repositories/Eloquent/EloquentFeatureRepository
```

4. **Create Service**
```bash
php artisan make:class Services/FeatureService
```

5. **Create Controller**
```bash
php artisan make:controller FeatureController --resource
```

6. **Create Policy**
```bash
php artisan make:policy FeaturePolicy --model=Feature
```

7. **Create Tests**
```bash
php artisan make:test FeatureTest
php artisan make:test FeatureServiceTest --unit
```

### Debugging

```php
// Use Laravel Debugbar (development only)
composer require barryvdh/laravel-debugbar --dev

// Dump and die
dd($variable);

// Dump without dying
dump($variable);

// Log debugging info
Log::debug('Debug info', ['data' => $data]);

// Query debugging
DB::enableQueryLog();
// ... run queries
dd(DB::getQueryLog());

// Ray debugging (if installed)
ray($variable);
ray()->table($array);
```

### Artisan Commands

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta

# Database
php artisan migrate:fresh --seed
php artisan db:seed
php artisan migrate:rollback
php artisan migrate:status

# Queue
php artisan queue:work
php artisan queue:listen
php artisan queue:restart
php artisan horizon

# Maintenance
php artisan down
php artisan up
php artisan optimize
php artisan optimize:clear
```

## Troubleshooting

### Common Issues

**Issue: Class not found**
```bash
composer dump-autoload
php artisan clear-compiled
php artisan config:clear
```

**Issue: Permission denied**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**Issue: Queue not processing**
```bash
php artisan queue:restart
php artisan horizon:terminate
```

**Issue: Cache not clearing**
```bash
php artisan cache:clear
php artisan config:clear
redis-cli FLUSHALL
```

**Issue: Slow queries**
```php
// Enable query logging
DB::listen(function ($query) {
    if ($query->time > 100) {
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

## Additional Resources

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [OpenAI API Documentation](https://platform.openai.com/docs)

### Tools
- [Laravel Telescope](https://laravel.com/docs/telescope) - Debugging assistant
- [Laravel Horizon](https://laravel.com/docs/horizon) - Queue monitoring
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) - Debug toolbar
- [PHPStan](https://phpstan.org) - Static analysis
- [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) - Code style

### Best Practices
- Follow PSR-12 coding standards
- Write tests for critical functionality
- Use type hints and return types
- Document complex logic with comments
- Keep controllers thin, services fat
- Use dependency injection
- Follow SOLID principles
- Keep security in mind always

---

For questions or contributions, contact the development team or refer to the project repository.
