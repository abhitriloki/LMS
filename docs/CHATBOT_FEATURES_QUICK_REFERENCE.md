# AI Chatbot Features - Quick Reference

## Overview

The AI Chatbot provides intelligent assistance for course recommendations, progress tracking, and enrollment help through natural language conversations.

## API Endpoints

All endpoints require authentication via Sanctum token.

### Send Message
```http
POST /api/chatbot/message
Content-Type: application/json
Authorization: Bearer {token}

{
  "message": "Can you recommend some courses?",
  "conversation_id": 123  // Optional, omit to start new conversation
}
```

**Response:**
```json
{
  "success": true,
  "conversation_id": 123,
  "message": {
    "id": 456,
    "content": "Based on your profile...",
    "role": "assistant",
    "created_at": "2024-01-15T10:30:00Z"
  },
  "intent": "course_recommendation",
  "confidence": 0.95
}
```

### Start New Conversation
```http
POST /api/chatbot/start
Authorization: Bearer {token}
```

### Get Conversation History
```http
GET /api/chatbot/conversations/{id}
Authorization: Bearer {token}
```

### Get All Conversations
```http
GET /api/chatbot/conversations
Authorization: Bearer {token}
```

### End Conversation
```http
POST /api/chatbot/conversations/{id}/end
Authorization: Bearer {token}
```

### Rate Conversation
```http
POST /api/chatbot/conversations/{id}/rate
Content-Type: application/json
Authorization: Bearer {token}

{
  "rating": 5,
  "feedback": "Very helpful!"
}
```

### Delete Conversation
```http
DELETE /api/chatbot/conversations/{id}
Authorization: Bearer {token}
```

## Supported Intents

### 1. Course Recommendation
**Triggers:** "recommend courses", "suggest courses", "what should I learn"

**Features:**
- AI-powered recommendations based on user profile
- Relevance scoring and reasoning
- Integration with AIRecommendation system
- Fallback to available courses

### 2. Progress Inquiry
**Triggers:** "my progress", "how am I doing", "show progress"

**Features:**
- Overall progress summary
- Recent courses with progress
- Specific course progress details
- Deadline tracking
- Motivational messages

### 3. Enrollment Assistance
**Triggers:** "enroll in", "sign up for", "take course"

**Features:**
- Enrollment by course ID or title
- Prerequisite validation
- Already enrolled detection
- Course details display
- Multiple course matching

### 4. Course Search
**Triggers:** "find course", "search for", "courses about"

**Features:**
- Full-text search across courses
- Category and difficulty filtering
- Enrollment status indicators

### 5. Greeting
**Triggers:** "hello", "hi", "hey"

**Response:** Personalized greeting with user name

### 6. Help
**Triggers:** "help", "what can you do"

**Response:** List of available features and capabilities

## Usage Examples

### Course Recommendations
```javascript
// Request
{
  "message": "Can you recommend some courses for me?"
}

// Response includes:
// - Personalized course list
// - Reasoning for each recommendation
// - Course IDs for easy enrollment
```

### Progress Inquiry
```javascript
// General progress
{
  "message": "What is my progress?"
}

// Specific course progress
{
  "message": "What is my progress in course 123?"
}

// Response includes:
// - Progress percentage
// - Completion status
// - Deadline information
// - Lesson completion count
```

### Enrollment Assistance
```javascript
// By course ID
{
  "message": "Enroll me in course 123"
}

// By course title
{
  "message": "I want to take Python Programming"
}

// Response includes:
// - Course details
// - Prerequisite check
// - Enrollment status
// - Next steps
```

## Service Methods

### AIChatbotService

```php
// Process a message
$result = $chatbotService->processMessage($message, $user, $context);
// Returns: ['response', 'intent', 'entities', 'confidence']

// Detect intent
$intent = $chatbotService->detectIntent($message);
// Returns: string (intent name)

// Search knowledge base
$results = $chatbotService->searchKnowledgeBase($query, $user);
// Returns: array with enrolled_courses, available_courses, user_progress

// Generate response
$response = $chatbotService->generateResponse($message, $user, $context);
// Returns: string (bot response)
```

## Testing

### Unit Tests
```bash
php artisan test --filter=AIChatbotServiceTest
```

**Coverage:**
- Intent detection (all intents)
- Message processing
- Course recommendations
- Progress inquiries
- Enrollment assistance
- Error handling
- Confidence scoring

### Feature Tests
```bash
php artisan test --filter=ChatbotTest
```

**Coverage:**
- API endpoints
- Conversation management
- Course recommendations
- Progress tracking
- Enrollment help
- Access control
- Validation

## Integration

### Frontend Integration

```javascript
// Send message
async function sendMessage(message, conversationId = null) {
  const response = await fetch('/api/chatbot/message', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ message, conversation_id: conversationId })
  });
  
  return await response.json();
}

// Start conversation
async function startConversation() {
  const response = await fetch('/api/chatbot/start', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  return await response.json();
}
```

### Alpine.js Component

The chatbot widget is already implemented in:
`resources/views/components/chatbot-widget.blade.php`

## Configuration

### AI Service
The chatbot uses the configured AI service (OpenAI by default) for:
- Intent detection
- Complex query responses
- Natural language understanding

### Caching
Intent detection results are cached for 1 hour to improve performance.

### Rate Limiting
API endpoints are protected by Laravel Sanctum authentication.

## Error Handling

The chatbot gracefully handles errors:
- AI service failures → Fallback responses
- Invalid course IDs → Helpful error messages
- Missing prerequisites → Clear prerequisite list
- Already enrolled → Current progress display

## Best Practices

1. **Always provide conversation_id** for continuing conversations
2. **Use specific course IDs** when possible for enrollment
3. **Handle low confidence responses** by offering alternatives
4. **Cache conversation history** on the frontend for better UX
5. **Implement typing indicators** for better user experience
6. **Show intent and confidence** for debugging (dev mode only)

## Troubleshooting

### Issue: Intent not detected correctly
**Solution:** Check AI service configuration and prompt templates

### Issue: Course not found
**Solution:** Verify course is published and ID is correct

### Issue: Prerequisites not showing
**Solution:** Ensure prerequisites array is properly set in course model

### Issue: Progress not updating
**Solution:** Check enrollment and lesson progress records

## Related Documentation

- [AI Chatbot UI Summary](./AI_CHATBOT_UI_SUMMARY.md)
- [AI Chatbot UI Testing Guide](./AI_CHATBOT_UI_TESTING_GUIDE.md)
- [Chatbot Features Summary](./CHATBOT_FEATURES_SUMMARY.md)
- [AI Service Foundation](./AI_SERVICE_FOUNDATION_SUMMARY.md)

## Support

For issues or questions:
1. Check the test files for usage examples
2. Review the service implementation
3. Check Laravel logs for errors
4. Verify AI service configuration
