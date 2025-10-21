# AI-Powered Corporate Learning Management System - Development Prompt

## Project Overview
Create a universal, enterprise-grade Learning Management System (LMS) with advanced AI integration for corporate training. This system should be built in Laravel with a modern, sophisticated frontend design that rivals premium SaaS platforms.

## Technology Stack Requirements

### Backend
- **Framework**: Laravel 11 (latest)
- **Database**: MySQL 8.0+ with proper indexing and optimization
- **Authentication**: Laravel Sanctum for API authentication
- **File Storage**: Laravel Storage with S3/Local support
- **Queue System**: Redis + Laravel Horizon for background jobs
- **Caching**: Redis for performance optimization
- **Search**: Laravel Scout with Algolia/Meilisearch
- **Real-time**: Laravel WebSockets/Pusher for notifications

### Frontend
- **Framework**: Laravel Blade + Alpine.js for reactivity
- **CSS**: Tailwind CSS with custom components
- **JavaScript**: Alpine.js, Livewire for dynamic interactions
- **UI Components**: Custom-built premium components
- **Charts**: Chart.js for analytics
- **File Upload**: Dropzone.js for drag-and-drop
- **Video Player**: Video.js with custom branding

### AI Integration
- **Primary**: OpenAI GPT-4 API
- **Backup**: Google Gemini API
- **Image Generation**: DALL-E 3 or Midjourney API
- **Speech-to-Text**: OpenAI Whisper API
- **Text-to-Speech**: OpenAI TTS API

## Design Requirements

### Visual Design Standards
- **Modern & Professional**: Clean, minimalist design with subtle gradients
- **Color Scheme**: 
  - Primary: Deep Blue (#1e40af) with gradient to lighter blue
  - Secondary: Emerald Green (#10b981) for success states
  - Accent: Purple (#8b5cf6) for AI features
  - Neutral: Grays from #f8fafc to #1e293b
- **Typography**: Inter font family with clear hierarchy
- **Spacing**: Consistent 8px grid system
- **Animations**: Smooth transitions using Tailwind animations
- **Icons**: Heroicons or Lucide icons throughout

### Layout Requirements
- **Responsive Design**: Mobile-first approach with breakpoints
- **Dashboard Layout**: Sidebar navigation with main content area
- **Card-Based UI**: Consistent card components with shadows and hover effects
- **Dark Mode**: Full dark/light theme toggle
- **Accessibility**: WCAG 2.1 AA compliance

### Component Design Standards
- **Buttons**: Multiple variants (primary, secondary, outline, ghost)
- **Forms**: Floating labels with validation states
- **Tables**: Sortable, searchable with pagination
- **Modals**: Smooth animations with backdrop blur
- **Notifications**: Toast notifications with auto-dismiss
- **Loading States**: Skeleton loaders and spinners

## Core Features Implementation

### 1. User Management & Authentication
```php
// Database Schema
users: id, name, email, password, role, department, position, avatar, 
       email_verified_at, last_login_at, created_at, updated_at

roles: id, name, permissions, created_at, updated_at

departments: id, name, description, manager_id, created_at, updated_at
```

**Features:**
- Multi-role authentication (Super Admin, HR Admin, Instructor, Employee)
- Department-based organization structure
- Profile management with avatar upload
- Activity logging and audit trails
- Password policies and 2FA support

### 2. Course Management System
```php
// Database Schema
courses: id, title, description, short_description, category_id, difficulty_level,
         estimated_duration, thumbnail, price, is_mandatory, is_published,
         prerequisites, learning_objectives, created_by, created_at, updated_at

course_modules: id, course_id, title, description, order_index, created_at, updated_at

course_lessons: id, module_id, title, content_type, content_path, duration,
                order_index, is_required, created_at, updated_at

course_categories: id, name, description, icon, color, parent_id, order_index, created_at
```

**Features:**
- Visual course builder with drag-and-drop
- Multi-format content support (video, PDF, SCORM, presentations)
- Course templates for quick creation
- Version control and draft management
- Bulk operations and course cloning
- Advanced search and filtering

### 3. Content Delivery System
```php
// Database Schema
lesson_progress: id, user_id, lesson_id, status, time_spent, completion_date,
                 last_position, created_at, updated_at

content_views: id, user_id, content_type, content_id, view_duration, 
               created_at, updated_at
```

**Features:**
- Adaptive video player with quality adjustment
- PDF viewer with annotations
- Interactive presentations
- Progress bookmarking
- Offline content download
- Content recommendations

### 4. Assessment Engine
```php
// Database Schema
assessments: id, course_id, title, description, instructions, passing_score,
             time_limit, max_attempts, randomize_questions, show_results,
             created_at, updated_at

questions: id, assessment_id, type, question_text, points, explanation,
           metadata, created_at, updated_at

question_options: id, question_id, option_text, is_correct, order_index, created_at

assessment_attempts: id, assessment_id, user_id, start_time, end_time, score,
                     status, attempt_number, created_at, updated_at

attempt_responses: id, attempt_id, question_id, response_text, selected_options,
                   is_correct, points_earned, feedback, created_at
```

**Features:**
- Multiple question types (MCQ, true/false, fill-in-blank, essay, drag-drop)
- Question bank with categorization
- Randomization and shuffling
- Timer with auto-submit
- Immediate feedback and explanations
- Proctoring features (webcam monitoring)

### 5. AI-Powered Features

#### 5.1 Content Recommendation Engine
```php
// Database Schema
ai_recommendations: id, user_id, course_id, recommendation_type, score,
                    reasoning, metadata, expires_at, created_at

recommendation_feedback: id, recommendation_id, user_id, action, feedback,
                         created_at
```

**Implementation:**
```php
class AIRecommendationService
{
    public function generateRecommendations(User $user): array
    {
        // Collect user data
        $profile = $this->collectUserProfile($user);
        $learningHistory = $this->getLearningHistory($user);
        $skillGaps = $this->analyzeSkillGaps($user);
        
        // Build AI prompt
        $prompt = $this->buildRecommendationPrompt($profile, $learningHistory, $skillGaps);
        
        // Call AI API
        $response = $this->aiService->generateRecommendations($prompt);
        
        // Process and store recommendations
        return $this->processRecommendations($response, $user);
    }
}
```

#### 5.2 AI Question Generator
```php
// Database Schema
question_generation_jobs: id, course_id, content_id, status, parameters,
                          created_at, completed_at

generated_questions: id, job_id, question_data, status, reviewed_by,
                     created_at, updated_at
```

**Implementation:**
```php
class AIQuestionGeneratorService
{
    public function generateQuestions($content, $parameters): array
    {
        // Extract text from content
        $text = $this->extractTextFromContent($content);
        
        // Build prompt for question generation
        $prompt = $this->buildQuestionPrompt($text, $parameters);
        
        // Generate questions using AI
        $questions = $this->aiService->generateQuestions($prompt);
        
        // Validate and format questions
        return $this->formatQuestions($questions);
    }
}
```

#### 5.3 AI Learning Path Optimizer
```php
// Database Schema
ai_learning_paths: id, user_id, path_data, target_role, status,
                   created_at, updated_at

path_adjustments: id, learning_path_id, adjustment_type, reason,
                  created_at
```

**Implementation:**
```php
class AILearningPathService
{
    public function generateLearningPath(User $user, $targetRole): array
    {
        // Analyze user profile and goals
        $analysis = $this->analyzeUserProfile($user, $targetRole);
        
        // Generate optimal learning sequence
        $path = $this->aiService->generateOptimalPath($analysis);
        
        // Validate prerequisites and dependencies
        return $this->validateAndOptimizePath($path, $user);
    }
}
```

#### 5.4 AI Content Analyzer
```php
// Database Schema
content_analysis: id, course_id, content_id, analysis_type, scores,
                  issues, suggestions, created_at
```

**Implementation:**
```php
class AIContentAnalyzerService
{
    public function analyzeContent($content): array
    {
        // Analyze readability and complexity
        $readability = $this->analyzeReadability($content);
        
        // Check for content gaps
        $gaps = $this->identifyContentGaps($content);
        
        // Generate improvement suggestions
        $suggestions = $this->generateSuggestions($content, $readability, $gaps);
        
        return compact('readability', 'gaps', 'suggestions');
    }
}
```

#### 5.5 AI Auto-Grading System
```php
// Database Schema
ai_grading_results: id, attempt_response_id, score, feedback, confidence,
                    requires_review, created_at, updated_at
```

**Implementation:**
```php
class AIGradingService
{
    public function gradeResponse($response, $rubric): array
    {
        // Analyze response against rubric
        $analysis = $this->analyzeResponse($response, $rubric);
        
        // Generate detailed feedback
        $feedback = $this->generateFeedback($analysis);
        
        // Calculate confidence score
        $confidence = $this->calculateConfidence($analysis);
        
        return compact('analysis', 'feedback', 'confidence');
    }
}
```

#### 5.6 AI Chatbot Assistant
```php
// Database Schema
chatbot_conversations: id, user_id, session_id, started_at, ended_at, created_at

chatbot_messages: id, conversation_id, sender_type, message, intent,
                  response, metadata, created_at
```

**Implementation:**
```php
class AIChatbotService
{
    public function processMessage($message, $context): string
    {
        // Detect intent and entities
        $intent = $this->detectIntent($message);
        
        // Search knowledge base
        $kbResult = $this->searchKnowledgeBase($message, $intent);
        
        // Generate contextual response
        $response = $this->generateResponse($message, $context, $kbResult);
        
        return $response;
    }
}
```

### 6. Certification System
```php
// Database Schema
certificate_templates: id, name, html_template, css_styles, background_image,
                       is_default, created_at, updated_at

certificates: id, user_id, course_id, template_id, certificate_number,
              issue_date, expiry_date, score, pdf_path, created_at
```

**Features:**
- Customizable certificate templates
- Automatic generation on course completion
- QR code verification system
- Digital signatures
- Bulk certificate generation
- Email delivery integration

### 7. Reporting & Analytics
```php
// Database Schema
analytics_events: id, user_id, event_type, properties, created_at

reports: id, name, type, parameters, file_path, generated_by, created_at
```

**Features:**
- Real-time dashboards
- Custom report builder
- Learning analytics
- Compliance tracking
- Export to multiple formats
- Scheduled report generation

## Frontend Implementation Requirements

### 1. Dashboard Design
```blade
<!-- resources/views/dashboard.blade.php -->
<x-app-layout>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stats Cards -->
        <x-dashboard-card 
            title="Total Courses" 
            value="{{ $totalCourses }}" 
            icon="academic-cap"
            trend="+12%"
            color="blue"
        />
        <!-- More cards... -->
    </div>
    
    <!-- Learning Progress Chart -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <x-chart type="line" :data="$learningProgress" />
    </div>
    
    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <x-activity-feed :activities="$recentActivities" />
        <x-upcoming-courses :courses="$upcomingCourses" />
    </div>
</x-app-layout>
```

### 2. Course Catalog
```blade
<!-- resources/views/courses/index.blade.php -->
<x-app-layout>
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Course Catalog</h1>
        <div class="flex space-x-4">
            <x-search-input placeholder="Search courses..." />
            <x-dropdown>
                <x-dropdown.trigger>Categories</x-dropdown.trigger>
                <x-dropdown.content>
                    <!-- Category options -->
                </x-dropdown.content>
            </x-dropdown>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($courses as $course)
            <x-course-card :course="$course" />
        @endforeach
    </div>
</x-app-layout>
```

### 3. AI Features Integration
```javascript
// resources/js/ai-features.js
class AIAssistant {
    constructor() {
        this.initChatbot();
        this.initRecommendations();
        this.initContentAnalyzer();
    }
    
    initChatbot() {
        // Initialize chat widget
        const chatWidget = new ChatWidget({
            position: 'bottom-right',
            theme: 'light',
            placeholder: 'Ask me anything about your learning...'
        });
    }
    
    async getRecommendations(userId) {
        const response = await fetch(`/api/ai/recommendations/${userId}`);
        const recommendations = await response.json();
        this.displayRecommendations(recommendations);
    }
}
```

## API Design

### RESTful API Structure
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Course Management
    Route::apiResource('courses', CourseController::class);
    Route::post('courses/{course}/enroll', [CourseController::class, 'enroll']);
    
    // AI Features
    Route::get('/ai/recommendations/{user}', [AIController::class, 'recommendations']);
    Route::post('/ai/generate-questions', [AIController::class, 'generateQuestions']);
    Route::post('/ai/analyze-content', [AIController::class, 'analyzeContent']);
    Route::post('/ai/grade-response', [AIController::class, 'gradeResponse']);
    
    // Chatbot
    Route::post('/chatbot/message', [ChatbotController::class, 'processMessage']);
    Route::get('/chatbot/history/{user}', [ChatbotController::class, 'history']);
    
    // Assessments
    Route::post('assessments/{assessment}/attempt', [AssessmentController::class, 'attempt']);
    Route::post('assessments/{assessment}/submit', [AssessmentController::class, 'submit']);
});
```

## Database Design

### Complete Schema
```sql
-- Users and Authentication
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'hr_admin', 'instructor', 'employee') DEFAULT 'employee',
    department_id BIGINT,
    position VARCHAR(255),
    avatar VARCHAR(255),
    phone VARCHAR(50),
    bio TEXT,
    preferences JSON,
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_department (department_id)
);

-- Departments
CREATE TABLE departments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    manager_id BIGINT,
    parent_id BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES departments(id)
);

-- Course Categories
CREATE TABLE course_categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(7),
    parent_id BIGINT,
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES course_categories(id),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
);

-- Courses
CREATE TABLE courses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    short_description TEXT,
    category_id BIGINT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    estimated_duration INT DEFAULT 0, -- in minutes
    thumbnail VARCHAR(255),
    preview_video VARCHAR(255),
    price DECIMAL(10,2) DEFAULT 0.00,
    is_mandatory BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    prerequisites JSON,
    learning_objectives JSON,
    target_audience TEXT,
    language VARCHAR(10) DEFAULT 'en',
    tags JSON,
    metadata JSON,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES course_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_category (category_id),
    INDEX idx_published (is_published),
    INDEX idx_featured (is_featured),
    INDEX idx_difficulty (difficulty_level),
    FULLTEXT idx_search (title, description, short_description)
);

-- Course Modules
CREATE TABLE course_modules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    course_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    is_mandatory BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_order (course_id, order_index)
);

-- Course Lessons
CREATE TABLE course_lessons (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    module_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    content_type ENUM('video', 'text', 'pdf', 'presentation', 'scorm', 'quiz', 'assignment') DEFAULT 'text',
    content_path VARCHAR(255),
    content_url VARCHAR(500),
    content_text LONGTEXT,
    duration INT DEFAULT 0, -- in minutes
    order_index INT DEFAULT 0,
    is_mandatory BOOLEAN DEFAULT TRUE,
    is_downloadable BOOLEAN DEFAULT FALSE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE,
    INDEX idx_module_order (module_id, order_index),
    INDEX idx_type (content_type)
);

-- Course Enrollments
CREATE TABLE course_enrollments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    course_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    enrollment_type ENUM('mandatory', 'optional', 'self_enrolled') DEFAULT 'optional',
    enrolled_by BIGINT,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline TIMESTAMP NULL,
    status ENUM('active', 'completed', 'expired', 'suspended') DEFAULT 'active',
    completion_date TIMESTAMP NULL,
    final_score DECIMAL(5,2),
    certificate_id BIGINT,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    last_accessed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by) REFERENCES users(id),
    FOREIGN KEY (certificate_id) REFERENCES certificates(id),
    UNIQUE KEY unique_enrollment (course_id, user_id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_deadline (deadline)
);

-- Lesson Progress
CREATE TABLE lesson_progress (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id BIGINT NOT NULL,
    lesson_id BIGINT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    time_spent INT DEFAULT 0, -- in seconds
    completion_date TIMESTAMP NULL,
    last_position INT DEFAULT 0, -- for video progress
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (enrollment_id, lesson_id),
    INDEX idx_status (status)
);

-- Assessments
CREATE TABLE assessments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    course_id BIGINT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructions TEXT,
    passing_score DECIMAL(5,2) DEFAULT 70.00,
    time_limit INT DEFAULT 0, -- in minutes, 0 = no limit
    max_attempts INT DEFAULT 3,
    randomize_questions BOOLEAN DEFAULT FALSE,
    randomize_options BOOLEAN DEFAULT FALSE,
    show_results BOOLEAN DEFAULT TRUE,
    show_correct_answers BOOLEAN DEFAULT FALSE,
    allow_review BOOLEAN DEFAULT TRUE,
    is_published BOOLEAN DEFAULT FALSE,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_course (course_id),
    INDEX idx_published (is_published)
);

-- Questions
CREATE TABLE questions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    assessment_id BIGINT NOT NULL,
    type ENUM('multiple_choice', 'true_false', 'fill_blank', 'essay', 'matching', 'drag_drop') DEFAULT 'multiple_choice',
    question_text LONGTEXT NOT NULL,
    question_media VARCHAR(255),
    points DECIMAL(5,2) DEFAULT 1.00,
    difficulty_level ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    explanation TEXT,
    hints JSON,
    metadata JSON,
    is_ai_generated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    INDEX idx_assessment (assessment_id),
    INDEX idx_type (type),
    INDEX idx_difficulty (difficulty_level)
);

-- Question Options
CREATE TABLE question_options (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    question_id BIGINT NOT NULL,
    option_text TEXT NOT NULL,
    option_media VARCHAR(255),
    is_correct BOOLEAN DEFAULT FALSE,
    order_index INT DEFAULT 0,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_question_order (question_id, order_index)
);

-- Assessment Attempts
CREATE TABLE assessment_attempts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    assessment_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    enrollment_id BIGINT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    score DECIMAL(5,2) DEFAULT 0.00,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('in_progress', 'submitted', 'graded', 'expired') DEFAULT 'in_progress',
    attempt_number INT DEFAULT 1,
    ip_address VARCHAR(45),
    user_agent TEXT,
    proctoring_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id),
    INDEX idx_user_assessment (user_id, assessment_id),
    INDEX idx_status (status)
);

-- Attempt Responses
CREATE TABLE attempt_responses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    attempt_id BIGINT NOT NULL,
    question_id BIGINT NOT NULL,
    response_text LONGTEXT,
    selected_options JSON,
    uploaded_files JSON,
    is_correct BOOLEAN DEFAULT NULL,
    points_earned DECIMAL(5,2) DEFAULT 0.00,
    ai_feedback TEXT,
    instructor_feedback TEXT,
    graded_by BIGINT,
    graded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES assessment_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id),
    UNIQUE KEY unique_response (attempt_id, question_id)
);

-- AI Recommendations
CREATE TABLE ai_recommendations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    course_id BIGINT,
    recommendation_type ENUM('course', 'learning_path', 'content') DEFAULT 'course',
    relevance_score DECIMAL(5,2) DEFAULT 0.00,
    reasoning TEXT,
    metadata JSON,
    status ENUM('active', 'accepted', 'rejected', 'expired') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    INDEX idx_user_type (user_id, recommendation_type),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at)
);

-- AI Generated Questions
CREATE TABLE ai_question_jobs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    course_id BIGINT NOT NULL,
    lesson_id BIGINT,
    content_text LONGTEXT,
    parameters JSON,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_questions INT DEFAULT 0,
    generated_questions INT DEFAULT 0,
    error_message TEXT,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_course (course_id)
);

-- Generated Questions
CREATE TABLE generated_questions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    job_id BIGINT NOT NULL,
    question_data JSON NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'modified') DEFAULT 'pending',
    reviewed_by BIGINT,
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES ai_question_jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_job_status (job_id, status)
);

-- AI Learning Paths
CREATE TABLE ai_learning_paths (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_role VARCHAR(255),
    path_data JSON NOT NULL,
    estimated_duration INT DEFAULT 0,
    difficulty_progression JSON,
    status ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_by_ai BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
);

-- AI Content Analysis
CREATE TABLE content_analysis (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    course_id BIGINT,
    lesson_id BIGINT,
    analysis_type ENUM('readability', 'completeness', 'engagement', 'accessibility') DEFAULT 'readability',
    scores JSON,
    issues JSON,
    suggestions JSON,
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE,
    INDEX idx_course_type (course_id, analysis_type),
    INDEX idx_lesson_type (lesson_id, analysis_type)
);

-- AI Grading Results
CREATE TABLE ai_grading_results (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    attempt_response_id BIGINT NOT NULL,
    ai_score DECIMAL(5,2),
    ai_feedback LONGTEXT,
    confidence_score DECIMAL(3,2) DEFAULT 0.00,
    requires_human_review BOOLEAN DEFAULT FALSE,
    reviewed_by BIGINT,
    final_score DECIMAL(5,2),
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_response_id) REFERENCES attempt_responses(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_response (attempt_response_id),
    INDEX idx_review_required (requires_human_review)
);

-- Chatbot Conversations
CREATE TABLE chatbot_conversations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    message_count INT DEFAULT 0,
    rating INT DEFAULT NULL,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_session (user_id, session_id),
    INDEX idx_started (started_at)
);

-- Chatbot Messages
CREATE TABLE chatbot_messages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT NOT NULL,
    sender_type ENUM('user', 'bot', 'human_agent') DEFAULT 'user',
    message_text LONGTEXT NOT NULL,
    intent_detected VARCHAR(255),
    confidence_score DECIMAL(3,2),
    response_text LONGTEXT,
    metadata JSON,
    is_escalated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chatbot_conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation_created (conversation_id, created_at),
    INDEX idx_intent (intent_detected)
);

-- Chatbot Knowledge Base
CREATE TABLE chatbot_knowledge_base (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(255),
    question TEXT NOT NULL,
    answer LONGTEXT NOT NULL,
    source_reference VARCHAR(255),
    usage_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    FULLTEXT idx_question_answer (question, answer)
);

-- Certificate Templates
CREATE TABLE certificate_templates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    html_template LONGTEXT NOT NULL,
    css_styles LONGTEXT,
    background_image VARCHAR(255),
    logo_position JSON,
    signature_positions JSON,
    variables JSON,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_active (is_active)
);

-- Certificates
CREATE TABLE certificates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    course_id BIGINT NOT NULL,
    template_id BIGINT,
    certificate_number VARCHAR(255) UNIQUE NOT NULL,
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NULL,
    score DECIMAL(5,2),
    pdf_path VARCHAR(255),
    verification_token VARCHAR(255) UNIQUE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES certificate_templates(id),
    INDEX idx_user_course (user_id, course_id),
    INDEX idx_certificate_number (certificate_number),
    INDEX idx_verification_token (verification_token)
);

-- Notifications
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_type (type),
    INDEX idx_created (created_at)
);

-- Analytics Events
CREATE TABLE analytics_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    event_type VARCHAR(100) NOT NULL,
    properties JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_type (user_id, event_type),
    INDEX idx_event_type (event_type),
    INDEX idx_created (created_at)
);

-- Reports
CREATE TABLE reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    parameters JSON,
    file_path VARCHAR(255),
    file_size BIGINT,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    generated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_type_status (type, status),
    INDEX idx_generated_by (generated_by)
);

-- Settings
CREATE TABLE settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    value LONGTEXT,
    type VARCHAR(50) DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (key),
    INDEX idx_public (is_public)
);
```

## Implementation Plan

### Phase 1: Foundation (Week 1-2)
1. **Project Setup**
   - Install Laravel 11 with proper configuration
   - Set up database with complete schema
   - Configure authentication and authorization
   - Set up file storage and caching

2. **Basic UI Framework**
   - Create layout templates
   - Implement responsive design system
   - Set up component library
   - Configure dark/light theme

### Phase 2: Core Features (Week 3-4)
1. **User Management**
   - Multi-role authentication
   - User profiles and preferences
   - Department management
   - Permission system

2. **Course Management**
   - Course CRUD operations
   - Module and lesson management
   - File upload and processing
   - Course categories

### Phase 3: Learning Features (Week 5-6)
1. **Content Delivery**
   - Video player integration
   - PDF viewer
   - Progress tracking
   - Enrollment system

2. **Assessment Engine**
   - Question bank
   - Assessment creation
   - Attempt management
   - Grading system

### Phase 4: AI Integration (Week 7-8)
1. **AI Services Setup**
   - OpenAI API integration
   - Prompt engineering
   - Response caching
   - Error handling

2. **AI Features Implementation**
   - Recommendation engine
   - Question generator
   - Content analyzer
   - Auto-grading system

### Phase 5: Advanced Features (Week 9-10)
1. **Chatbot System**
   - Knowledge base
   - Natural language processing
   - Conversation management
   - Escalation system

2. **Analytics & Reporting**
   - Event tracking
   - Dashboard creation
   - Report generation
   - Data visualization

### Phase 6: Polish & Launch (Week 11-12)
1. **Performance Optimization**
   - Database optimization
   - Caching strategies
   - CDN integration
   - Load testing

2. **Final Touches**
   - Security audit
   - Testing suite
   - Documentation
   - Deployment preparation

## Quality Standards

### Code Quality
- Follow PSR-12 coding standards
- Implement comprehensive error handling
- Use type hints and return types
- Write unit and integration tests
- Document all APIs and complex logic

### Performance Standards
- Page load time < 2 seconds
- API response time < 500ms
- Database query optimization
- Efficient memory usage
- Proper caching strategies

### Security Standards
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection
- Rate limiting
- Secure file uploads

### UI/UX Standards
- Mobile-first responsive design
- Accessibility compliance (WCAG 2.1 AA)
- Intuitive navigation
- Consistent design language
- Smooth animations and transitions

## Testing Strategy

### Unit Tests
- Model relationships and validations
- Service class methods
- Utility functions
- AI service integrations

### Feature Tests
- User authentication flows
- Course enrollment processes
- Assessment taking and grading
- AI feature interactions

### Browser Tests
- Cross-browser compatibility
- Mobile responsiveness
- JavaScript functionality
- User interactions

### Performance Tests
- Load testing with concurrent users
- Database query performance
- API endpoint stress testing
- Memory usage monitoring

## Deployment Requirements

### Environment Setup
- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- Node.js 18+
- Nginx/Apache

### Production Configuration
- Environment-based configuration
- SSL certificate setup
- Database replication
- Backup strategies
- Monitoring and logging

### CI/CD Pipeline
- Automated testing
- Code quality checks
- Security scanning
- Staging environment
- Zero-downtime deployment

## Success Metrics

### Technical Metrics
- 99.9% uptime
- < 2 second page load times
- < 1% error rate
- 90+ test coverage

### User Metrics
- User engagement > 70%
- Course completion rate > 80%
- User satisfaction > 4.5/5
- Support ticket reduction > 50%

### Business Metrics
- Development timeline adherence
- Budget compliance
- Feature completeness > 95%
- Client satisfaction > 4.5/5

---

**Note to Developer:** This is a comprehensive enterprise LMS project that requires attention to detail, modern development practices, and a focus on user experience. The AI features should be implemented with proper error handling and fallback mechanisms. The design should be professional, modern, and accessible. Please prioritize security, performance, and maintainability throughout the development process.