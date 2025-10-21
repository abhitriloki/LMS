<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class AIChatbotService
{
    protected AIServiceInterface $aiService;

    public function __construct(AIServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Process a user message and generate a response
     */
    public function processMessage(string $message, User $user, array $context = []): array
    {
        try {
            // Detect intent from the message
            $intent = $this->detectIntent($message);
            
            // Extract entities from the message
            $entities = $this->extractEntities($message, $intent);
            
            // Search knowledge base for relevant information
            $knowledgeBaseResults = $this->searchKnowledgeBase($message, $user);
            
            // Generate contextual response
            $response = $this->generateResponse($message, $user, [
                'intent' => $intent,
                'entities' => $entities,
                'knowledge_base' => $knowledgeBaseResults,
                'conversation_context' => $context,
            ]);
            
            return [
                'response' => $response,
                'intent' => $intent,
                'entities' => $entities,
                'confidence' => $this->calculateConfidence($intent, $entities),
            ];
            
        } catch (\Exception $e) {
            Log::error('Chatbot message processing failed', [
                'message' => $message,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'response' => "I'm sorry, I'm having trouble understanding that right now. Could you please rephrase your question?",
                'intent' => 'unknown',
                'entities' => [],
                'confidence' => 0.0,
            ];
        }
    }

    /**
     * Detect the intent of a user message
     */
    public function detectIntent(string $message): string
    {
        $cacheKey = 'chatbot_intent_' . md5($message);
        
        return Cache::remember($cacheKey, 3600, function () use ($message) {
            $prompt = $this->buildIntentDetectionPrompt($message);
            
            try {
                $response = $this->aiService->generateText($prompt, [
                    'temperature' => 0.3,
                    'max_tokens' => 50,
                ]);
                
                return $this->parseIntent($response);
                
            } catch (AIServiceException $e) {
                Log::warning('Intent detection failed', ['error' => $e->getMessage()]);
                return 'general_inquiry';
            }
        });
    }

    /**
     * Extract entities from the message
     */
    protected function extractEntities(string $message, string $intent): array
    {
        $entities = [];
        
        // Extract course names/IDs
        if (preg_match('/course\s+(?:id\s+)?(\d+)/i', $message, $matches)) {
            $entities['course_id'] = (int) $matches[1];
        }
        
        // Extract course titles (quoted or capitalized)
        if (preg_match('/"([^"]+)"|\'([^\']+)\'/', $message, $matches)) {
            $entities['course_title'] = $matches[1] ?? $matches[2];
        }
        
        // Extract time-related entities
        if (preg_match('/\b(today|tomorrow|this week|next week|this month)\b/i', $message, $matches)) {
            $entities['time_reference'] = strtolower($matches[1]);
        }
        
        // Extract status-related entities
        if (preg_match('/\b(completed|in progress|not started|enrolled)\b/i', $message, $matches)) {
            $entities['status'] = strtolower($matches[1]);
        }
        
        return $entities;
    }

    /**
     * Search the knowledge base for relevant information
     */
    public function searchKnowledgeBase(string $query, User $user): array
    {
        $results = [];
        
        // Search user's enrolled courses
        $enrolledCourses = $this->searchUserCourses($query, $user);
        if ($enrolledCourses->isNotEmpty()) {
            $results['enrolled_courses'] = $enrolledCourses->take(5)->toArray();
        }
        
        // Search available courses
        $availableCourses = $this->searchAvailableCourses($query, $user);
        if ($availableCourses->isNotEmpty()) {
            $results['available_courses'] = $availableCourses->take(5)->toArray();
        }
        
        // Get user progress data
        $results['user_progress'] = $this->getUserProgressSummary($user);
        
        return $results;
    }

    /**
     * Generate a contextual response
     */
    public function generateResponse(string $message, User $user, array $context): string
    {
        $intent = $context['intent'] ?? 'general_inquiry';
        
        // Handle specific intents with predefined responses
        switch ($intent) {
            case 'course_recommendation':
                return $this->handleCourseRecommendation($user, $context);
                
            case 'progress_inquiry':
                return $this->handleProgressInquiry($user, $context);
                
            case 'enrollment_assistance':
                return $this->handleEnrollmentAssistance($user, $context);
                
            case 'course_search':
                return $this->handleCourseSearch($user, $context);
                
            case 'greeting':
                return $this->handleGreeting($user);
                
            case 'help':
                return $this->handleHelp();
                
            default:
                return $this->generateAIResponse($message, $user, $context);
        }
    }

    /**
     * Handle course recommendation requests
     */
    protected function handleCourseRecommendation(User $user, array $context): string
    {
        $knowledgeBase = $context['knowledge_base'] ?? [];
        $availableCourses = $knowledgeBase['available_courses'] ?? [];
        
        // Get AI-powered recommendations if available
        $aiRecommendations = $this->getAIRecommendations($user);
        
        if (empty($availableCourses) && empty($aiRecommendations)) {
            return "I don't have any specific course recommendations for you at the moment. You can browse our course catalog to find courses that match your interests.";
        }
        
        $response = "Based on your profile, role as {$user->role}, and learning history, I recommend the following courses:\n\n";
        
        // Prioritize AI recommendations
        $recommendations = !empty($aiRecommendations) ? $aiRecommendations : $availableCourses;
        
        foreach (array_slice($recommendations, 0, 3) as $index => $course) {
            $courseTitle = $course['title'] ?? $course['course_title'] ?? 'Unknown Course';
            $courseDesc = $course['short_description'] ?? $course['description'] ?? '';
            $courseId = $course['id'] ?? $course['course_id'] ?? null;
            $reason = $course['reason'] ?? null;
            
            $response .= ($index + 1) . ". **{$courseTitle}**";
            if ($courseId) {
                $response .= " (ID: {$courseId})";
            }
            $response .= "\n";
            
            if ($courseDesc) {
                $response .= "   {$courseDesc}\n";
            }
            
            if ($reason) {
                $response .= "   *Why this course?* {$reason}\n";
            }
            
            $response .= "\n";
        }
        
        $response .= "Would you like to enroll in any of these courses? Just let me know the course ID or name!";
        
        return $response;
    }
    
    /**
     * Get AI-powered recommendations for the user
     */
    protected function getAIRecommendations(User $user): array
    {
        $recommendations = \App\Models\AIRecommendation::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('course')
            ->orderBy('relevance_score', 'desc')
            ->take(5)
            ->get();
        
        return $recommendations->map(function ($rec) {
            return [
                'id' => $rec->course_id,
                'course_id' => $rec->course_id,
                'title' => $rec->course->title,
                'short_description' => $rec->course->short_description,
                'difficulty_level' => $rec->course->difficulty_level,
                'reason' => $rec->reasoning,
                'relevance_score' => $rec->relevance_score,
            ];
        })->toArray();
    }

    /**
     * Handle progress inquiry requests
     */
    protected function handleProgressInquiry(User $user, array $context): string
    {
        $progressData = $context['knowledge_base']['user_progress'] ?? [];
        $entities = $context['entities'] ?? [];
        
        if (empty($progressData) || $progressData['total_enrolled'] === 0) {
            return "You haven't enrolled in any courses yet. Would you like me to recommend some courses for you?";
        }
        
        // Check if user is asking about a specific course
        if (isset($entities['course_id']) || isset($entities['course_title'])) {
            return $this->handleSpecificCourseProgress($user, $entities);
        }
        
        $response = "Here's your learning progress summary:\n\n";
        $response .= "📚 **Total Courses Enrolled**: {$progressData['total_enrolled']}\n";
        $response .= "✅ **Courses Completed**: {$progressData['completed']}\n";
        $response .= "📖 **Courses In Progress**: {$progressData['in_progress']}\n";
        $response .= "📊 **Overall Progress**: {$progressData['overall_progress']}%\n\n";
        
        if (!empty($progressData['recent_courses'])) {
            $response .= "**Your Recent Courses:**\n";
            foreach ($progressData['recent_courses'] as $course) {
                $statusEmoji = $course['status'] === 'completed' ? '✅' : '📖';
                $response .= "{$statusEmoji} {$course['title']} - {$course['progress']}% complete\n";
            }
            $response .= "\n";
        }
        
        // Add motivational message based on progress
        if ($progressData['overall_progress'] >= 80) {
            $response .= "🎉 Excellent progress! You're doing great! Keep up the good work!";
        } elseif ($progressData['overall_progress'] >= 50) {
            $response .= "👍 Good progress! You're halfway there. Keep learning!";
        } else {
            $response .= "💪 You've started your learning journey! Keep going to reach your goals!";
        }
        
        return $response;
    }
    
    /**
     * Handle progress inquiry for a specific course
     */
    protected function handleSpecificCourseProgress(User $user, array $entities): string
    {
        $course = null;
        
        if (isset($entities['course_id'])) {
            $course = Course::find($entities['course_id']);
        } elseif (isset($entities['course_title'])) {
            $course = Course::where('title', 'like', '%' . $entities['course_title'] . '%')->first();
        }
        
        if (!$course) {
            return "I couldn't find that course. Please check the course name or ID and try again.";
        }
        
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        
        if (!$enrollment) {
            return "You're not enrolled in **{$course->title}**. Would you like to enroll in this course?";
        }
        
        $response = "**{$course->title}** Progress:\n\n";
        $response .= "📊 **Progress**: {$enrollment->progress_percentage}%\n";
        $response .= "📅 **Status**: " . ucfirst($enrollment->status) . "\n";
        $response .= "🕐 **Last Accessed**: " . $enrollment->last_accessed_at?->diffForHumans() ?? 'Never' . "\n";
        
        if ($enrollment->deadline) {
            $daysLeft = now()->diffInDays($enrollment->deadline, false);
            if ($daysLeft > 0) {
                $response .= "⏰ **Deadline**: {$enrollment->deadline->format('M d, Y')} ({$daysLeft} days left)\n";
            } else {
                $response .= "⚠️ **Deadline**: Overdue by " . abs($daysLeft) . " days\n";
            }
        }
        
        if ($enrollment->status === 'completed') {
            $response .= "\n✅ Congratulations! You've completed this course";
            if ($enrollment->final_score) {
                $response .= " with a score of {$enrollment->final_score}%";
            }
            $response .= "!";
        } else {
            $completedLessons = $enrollment->lessonProgress()->where('is_completed', true)->count();
            $totalLessons = $course->lessons()->count();
            $response .= "\n📚 **Lessons Completed**: {$completedLessons} / {$totalLessons}\n";
            $response .= "\nKeep going! You're making great progress!";
        }
        
        return $response;
    }

    /**
     * Handle enrollment assistance requests
     */
    protected function handleEnrollmentAssistance(User $user, array $context): string
    {
        $entities = $context['entities'] ?? [];
        
        if (isset($entities['course_id'])) {
            return $this->handleEnrollmentByCourseId($user, $entities['course_id']);
        }
        
        if (isset($entities['course_title'])) {
            return $this->handleEnrollmentByCourseTitle($user, $entities['course_title']);
        }
        
        return "I can help you enroll in a course! Here's what you can do:\n\n" .
               "• Tell me the course ID (e.g., 'enroll in course 123')\n" .
               "• Tell me the course name (e.g., 'enroll in Python Programming')\n" .
               "• Ask me to recommend courses based on your interests\n\n" .
               "What would you like to do?";
    }
    
    /**
     * Handle enrollment by course ID
     */
    protected function handleEnrollmentByCourseId(User $user, int $courseId): string
    {
        $course = Course::with('category')->find($courseId);
        
        if (!$course) {
            return "I couldn't find a course with ID {$courseId}. Please check the course ID or browse the course catalog.";
        }
        
        if (!$course->is_published) {
            return "**{$course->title}** is not currently available for enrollment. Please check back later or contact your administrator.";
        }
        
        // Check if already enrolled
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        
        if ($enrollment) {
            $statusMsg = $enrollment->status === 'completed' 
                ? "You've already completed this course!" 
                : "You're already enrolled in this course with {$enrollment->progress_percentage}% progress.";
            
            return "**{$course->title}**\n\n{$statusMsg}\n\n" .
                   "Would you like to continue learning or view your progress?";
        }
        
        // Check prerequisites
        if (!empty($course->prerequisites)) {
            $missingPrereqs = $this->checkPrerequisites($user, $course);
            if (!empty($missingPrereqs)) {
                $prereqList = implode(', ', array_map(function($p) {
                    return "**{$p['title']}**";
                }, $missingPrereqs));
                
                return "To enroll in **{$course->title}**, you need to complete these prerequisite courses first:\n\n" .
                       $prereqList . "\n\n" .
                       "Would you like information about these courses?";
            }
        }
        
        // Provide course details
        $response = "📚 **{$course->title}**\n\n";
        $response .= "📂 Category: {$course->category->name}\n";
        $response .= "📊 Level: " . ucfirst($course->difficulty_level) . "\n";
        $response .= "⏱️ Duration: " . $this->formatDuration($course->estimated_duration) . "\n";
        
        if ($course->short_description) {
            $response .= "\n{$course->short_description}\n";
        }
        
        $response .= "\nTo enroll, visit the course page or I can guide you through the enrollment process. Would you like to proceed?";
        
        return $response;
    }
    
    /**
     * Handle enrollment by course title
     */
    protected function handleEnrollmentByCourseTitle(User $user, string $courseTitle): string
    {
        $courses = Course::where('title', 'like', '%' . $courseTitle . '%')
            ->where('is_published', true)
            ->with('category')
            ->take(5)
            ->get();
        
        if ($courses->isEmpty()) {
            return "I couldn't find any courses matching '{$courseTitle}'. Would you like me to:\n\n" .
                   "• Suggest similar courses\n" .
                   "• Recommend courses based on your profile\n" .
                   "• Search for courses in a specific category";
        }
        
        if ($courses->count() === 1) {
            return $this->handleEnrollmentByCourseId($user, $courses->first()->id);
        }
        
        $response = "I found {$courses->count()} courses matching '{$courseTitle}':\n\n";
        
        foreach ($courses as $index => $course) {
            $enrollmentStatus = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            
            $statusIcon = $enrollmentStatus 
                ? ($enrollmentStatus->status === 'completed' ? '✅' : '📖') 
                : '📚';
            
            $response .= ($index + 1) . ". {$statusIcon} **{$course->title}** (ID: {$course->id})\n";
            $response .= "   {$course->category->name} • " . ucfirst($course->difficulty_level) . "\n";
            
            if ($enrollmentStatus) {
                $response .= "   *Already enrolled - {$enrollmentStatus->progress_percentage}% complete*\n";
            }
            
            $response .= "\n";
        }
        
        $response .= "Please tell me the course ID you'd like to enroll in, or ask for more details about a specific course.";
        
        return $response;
    }
    
    /**
     * Check prerequisites for a course
     */
    protected function checkPrerequisites(User $user, Course $course): array
    {
        if (empty($course->prerequisites)) {
            return [];
        }
        
        $missingPrereqs = [];
        
        foreach ($course->prerequisites as $prereqId) {
            $completed = Enrollment::where('user_id', $user->id)
                ->where('course_id', $prereqId)
                ->where('status', 'completed')
                ->exists();
            
            if (!$completed) {
                $prereqCourse = Course::find($prereqId);
                if ($prereqCourse) {
                    $missingPrereqs[] = [
                        'id' => $prereqCourse->id,
                        'title' => $prereqCourse->title,
                    ];
                }
            }
        }
        
        return $missingPrereqs;
    }
    
    /**
     * Format duration in minutes to human-readable format
     */
    protected function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes === 0) {
            return $hours === 1 ? "1 hour" : "{$hours} hours";
        }
        
        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Handle course search requests
     */
    protected function handleCourseSearch(User $user, array $context): string
    {
        $knowledgeBase = $context['knowledge_base'] ?? [];
        $availableCourses = $knowledgeBase['available_courses'] ?? [];
        
        if (empty($availableCourses)) {
            return "I couldn't find any courses matching your search. Try using different keywords or browse our course catalog.";
        }
        
        $response = "I found the following courses:\n\n";
        
        foreach (array_slice($availableCourses, 0, 5) as $course) {
            $response .= "• **{$course['title']}** (ID: {$course['id']}) - {$course['difficulty_level']}\n";
            $response .= "  {$course['short_description']}\n\n";
        }
        
        $response .= "Would you like more information about any of these courses?";
        
        return $response;
    }

    /**
     * Handle greeting messages
     */
    protected function handleGreeting(User $user): string
    {
        $greetings = [
            "Hello {$user->name}! How can I assist you with your learning today?",
            "Hi {$user->name}! I'm here to help you with courses, progress tracking, and recommendations. What would you like to know?",
            "Welcome back, {$user->name}! What can I help you with today?",
        ];
        
        return $greetings[array_rand($greetings)];
    }

    /**
     * Handle help requests
     */
    protected function handleHelp(): string
    {
        return "I can help you with:\n\n" .
               "• **Course Recommendations** - Get personalized course suggestions\n" .
               "• **Progress Tracking** - Check your learning progress\n" .
               "• **Course Enrollment** - Enroll in courses\n" .
               "• **Course Search** - Find courses by topic or title\n" .
               "• **General Questions** - Ask me anything about the learning platform\n\n" .
               "Just ask me a question, and I'll do my best to help!";
    }

    /**
     * Generate AI-powered response for complex queries
     */
    protected function generateAIResponse(string $message, User $user, array $context): string
    {
        $prompt = $this->buildResponsePrompt($message, $user, $context);
        
        try {
            return $this->aiService->generateText($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);
        } catch (AIServiceException $e) {
            Log::error('AI response generation failed', ['error' => $e->getMessage()]);
            return "I'm having trouble generating a response right now. Please try rephrasing your question or contact support for assistance.";
        }
    }

    /**
     * Build intent detection prompt
     */
    protected function buildIntentDetectionPrompt(string $message): string
    {
        return "Classify the following user message into one of these intents: " .
               "course_recommendation, progress_inquiry, enrollment_assistance, course_search, greeting, help, general_inquiry.\n\n" .
               "User message: \"{$message}\"\n\n" .
               "Respond with only the intent name, nothing else.";
    }

    /**
     * Build response generation prompt
     */
    protected function buildResponsePrompt(string $message, User $user, array $context): string
    {
        $knowledgeBase = $context['knowledge_base'] ?? [];
        
        $prompt = "You are a helpful learning assistant for a corporate LMS platform. ";
        $prompt .= "The user's name is {$user->name} and their role is {$user->role}.\n\n";
        
        if (!empty($knowledgeBase['user_progress'])) {
            $progress = $knowledgeBase['user_progress'];
            $prompt .= "User's learning progress:\n";
            $prompt .= "- Total enrolled courses: {$progress['total_enrolled']}\n";
            $prompt .= "- Completed courses: {$progress['completed']}\n";
            $prompt .= "- Overall progress: {$progress['overall_progress']}%\n\n";
        }
        
        if (!empty($knowledgeBase['available_courses'])) {
            $prompt .= "Available courses:\n";
            foreach (array_slice($knowledgeBase['available_courses'], 0, 3) as $course) {
                $prompt .= "- {$course['title']}: {$course['short_description']}\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "User's question: \"{$message}\"\n\n";
        $prompt .= "Provide a helpful, concise response. Be friendly and professional. ";
        $prompt .= "If you mention courses, include their titles. Keep the response under 200 words.";
        
        return $prompt;
    }

    /**
     * Parse intent from AI response
     */
    protected function parseIntent(string $response): string
    {
        $response = strtolower(trim($response));
        
        $validIntents = [
            'course_recommendation',
            'progress_inquiry',
            'enrollment_assistance',
            'course_search',
            'greeting',
            'help',
            'general_inquiry',
        ];
        
        foreach ($validIntents as $intent) {
            if (str_contains($response, $intent)) {
                return $intent;
            }
        }
        
        return 'general_inquiry';
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence(string $intent, array $entities): float
    {
        $confidence = 0.5; // Base confidence
        
        // Increase confidence for specific intents
        if (in_array($intent, ['greeting', 'help'])) {
            $confidence = 0.95;
        } elseif ($intent !== 'general_inquiry') {
            $confidence = 0.75;
        }
        
        // Increase confidence if entities were extracted
        if (!empty($entities)) {
            $confidence += 0.15;
        }
        
        return min($confidence, 1.0);
    }

    /**
     * Search user's enrolled courses
     */
    protected function searchUserCourses(string $query, User $user): Collection
    {
        return Course::whereHas('enrollments', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        })
        ->select('id', 'title', 'short_description', 'difficulty_level')
        ->get();
    }

    /**
     * Search available courses
     */
    protected function searchAvailableCourses(string $query, User $user): Collection
    {
        return Course::where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('tags', 'like', "%{$query}%");
            })
            ->whereDoesntHave('enrollments', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->select('id', 'title', 'short_description', 'difficulty_level')
            ->limit(10)
            ->get();
    }

    /**
     * Get user progress summary
     */
    protected function getUserProgressSummary(User $user): array
    {
        $enrollments = Enrollment::where('user_id', $user->id)->get();
        
        $totalEnrolled = $enrollments->count();
        $completed = $enrollments->where('status', 'completed')->count();
        $inProgress = $enrollments->where('status', 'in_progress')->count();
        $overallProgress = $totalEnrolled > 0 
            ? round($enrollments->avg('progress_percentage'), 2) 
            : 0;
        
        $recentCourses = $enrollments->sortByDesc('last_accessed_at')
            ->take(3)
            ->map(function ($enrollment) {
                return [
                    'title' => $enrollment->course->title,
                    'progress' => round($enrollment->progress_percentage, 2),
                    'status' => $enrollment->status,
                ];
            })
            ->values()
            ->toArray();
        
        return [
            'total_enrolled' => $totalEnrolled,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'overall_progress' => $overallProgress,
            'recent_courses' => $recentCourses,
        ];
    }
}
