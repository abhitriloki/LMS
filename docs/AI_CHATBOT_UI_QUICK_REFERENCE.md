# AI Chatbot UI - Quick Reference Guide

## Component Usage

### Basic Inclusion
```blade
<x-chatbot-widget />
```

### Custom Position
```blade
<x-chatbot-widget position="bottom-left" />
```

## User Actions

### Opening Chat
- Click floating chat button at bottom-right
- Widget expands to show chat window

### Sending Messages
- Type message in textarea
- Press **Enter** to send
- Press **Shift+Enter** for new line
- Click send button

### Starting New Conversation
- Click **+** button in header
- Confirm to start fresh conversation
- Previous conversation is saved

### Closing Chat
- Click minimize button (down arrow)
- Widget collapses to floating button

## Message Formatting

### Bold Text
```
**Important text**
```
Renders as: **Important text**

### Bullet Lists
```
• Item 1
• Item 2
• Item 3
```

### Line Breaks
Use Shift+Enter or natural line breaks

## Features

### Auto-Save
- Conversations automatically saved
- Resume from where you left off
- Stored in browser localStorage

### Typing Indicator
- Shows when AI is processing
- Animated dots indicate activity

### Error Handling
- Network errors shown inline
- Dismissible error messages
- Retry capability maintained

### Dark Mode
- Automatically follows system theme
- Toggle via theme switcher
- Smooth transitions

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Enter | Send message |
| Shift+Enter | New line |
| Tab | Navigate elements |

## API Endpoints

### Send Message
```
POST /chatbot/message
Body: { message, conversation_id }
```

### Get Conversation
```
GET /chatbot/conversations/{id}
```

### Start Conversation
```
POST /chatbot/conversations/start
```

### End Conversation
```
POST /chatbot/conversations/{id}/end
```

### Rate Conversation
```
POST /chatbot/conversations/{id}/rate
Body: { rating, feedback }
```

## Component Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| position | string | 'bottom-right' | Widget position on screen |

## State Variables

| Variable | Type | Description |
|----------|------|-------------|
| isOpen | boolean | Chat window open state |
| messages | array | Conversation messages |
| inputMessage | string | Current input text |
| isTyping | boolean | AI typing indicator |
| isSending | boolean | Message sending state |
| error | string | Error message |
| conversationId | number | Current conversation ID |
| autoScroll | boolean | Auto-scroll enabled |

## Methods

### init()
Initialize component, load saved conversation

### toggleChat()
Open/close chat window

### sendMessage()
Send user message to AI

### loadConversation()
Load conversation history from server

### startNewConversation()
Start fresh conversation

### scrollToBottom()
Scroll messages to bottom

### formatMessage(content)
Format message with markdown

### formatTime(timestamp)
Format timestamp to relative time

## Styling Classes

### Primary Colors
- `bg-primary-600` - Primary background
- `text-primary-600` - Primary text
- `hover:bg-primary-700` - Primary hover

### Message Bubbles
- User: `bg-primary-600 text-white rounded-br-none`
- Bot: `bg-white dark:bg-gray-800 rounded-bl-none`

### States
- Disabled: `disabled:bg-gray-300 disabled:cursor-not-allowed`
- Loading: `animate-spin` or `animate-bounce`

## Common Customizations

### Change Widget Position
```blade
<x-chatbot-widget position="bottom-left" />
```

### Modify Colors
Edit Tailwind classes in component:
- `bg-primary-600` → `bg-blue-600`
- `text-primary-600` → `text-blue-600`

### Adjust Size
Modify in component:
- Width: `w-96` (384px)
- Height: `h-[600px]`

### Custom Welcome Message
Edit welcome section in component template

## Troubleshooting

### Chat Not Opening
- Check JavaScript console for errors
- Verify Alpine.js is loaded
- Check CSRF token is present

### Messages Not Sending
- Verify API routes are registered
- Check authentication middleware
- Inspect network tab for errors

### Conversation Not Persisting
- Check localStorage is enabled
- Verify conversation ID is saved
- Check browser console for errors

### Styling Issues
- Verify Tailwind CSS is compiled
- Check dark mode classes
- Inspect element styles

## Browser Support

- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- Mobile browsers: ✅ Responsive

## Performance Tips

1. **Lazy Load:** Widget loads on demand
2. **Cache Conversations:** Uses localStorage
3. **Optimize Scroll:** Only auto-scrolls when needed
4. **Debounce Input:** Prevents excessive updates

## Accessibility

- ✅ Keyboard navigation
- ✅ Screen reader support
- ✅ High contrast mode
- ✅ Focus indicators
- ✅ ARIA labels

## Integration Points

### Layouts
- `resources/views/layouts/dashboard.blade.php`
- `resources/views/layouts/app.blade.php`

### Controllers
- `app/Http/Controllers/ChatbotController.php`

### Services
- `app/Services/AI/AIChatbotService.php`

### Models
- `app/Models/ChatbotConversation.php`
- `app/Models/ChatbotMessage.php`

## Example Interactions

### Course Recommendation
```
User: "What courses do you recommend for me?"
Bot: "Based on your profile, I recommend:
• Introduction to Data Science
• Python Programming
• Machine Learning Basics"
```

### Progress Inquiry
```
User: "What's my progress?"
Bot: "Here's your learning progress:
• Total Courses Enrolled: 5
• Courses Completed: 2
• Overall Progress: 65%"
```

### Enrollment Assistance
```
User: "How do I enroll in course 123?"
Bot: "I can help you enroll in Introduction to AI. 
This course is beginner level and takes approximately 
120 minutes. Would you like to enroll now?"
```

## Best Practices

1. **Keep Messages Concise:** Short, clear responses
2. **Use Formatting:** Bold important terms
3. **Provide Context:** Include relevant details
4. **Handle Errors:** Show user-friendly messages
5. **Test Thoroughly:** All user flows and edge cases

## Related Documentation

- [AI Chatbot Service Summary](AI_CHATBOT_SERVICE_SUMMARY.md)
- [AI Service Foundation](AI_SERVICE_FOUNDATION_SUMMARY.md)
- [Design System Guide](DESIGN_SYSTEM.md)
