# AI Chatbot UI - Testing Guide

## Overview

This guide provides comprehensive testing procedures for the AI Chatbot UI component to ensure all functionality works correctly across different scenarios and environments.

## Prerequisites

- Laravel application running
- User authenticated
- Database seeded with test data
- AI service configured (or mocked)

## Manual Testing Checklist

### 1. Component Rendering

#### Test 1.1: Widget Visibility
- [ ] Navigate to dashboard
- [ ] Verify floating chat button appears at bottom-right
- [ ] Check button has chat icon
- [ ] Verify button has hover effect
- [ ] Confirm button shows tooltip on hover

**Expected Result:** Floating button visible and interactive

#### Test 1.2: Dark Mode Rendering
- [ ] Toggle dark mode
- [ ] Verify button colors adjust
- [ ] Check contrast is maintained
- [ ] Confirm all elements are visible

**Expected Result:** Widget properly styled in dark mode

### 2. Opening and Closing Chat

#### Test 2.1: Open Chat Window
- [ ] Click floating chat button
- [ ] Verify chat window opens with animation
- [ ] Check window dimensions (384px × 600px)
- [ ] Confirm welcome message displays
- [ ] Verify input field is focused

**Expected Result:** Chat window opens smoothly with welcome content

#### Test 2.2: Close Chat Window
- [ ] Click minimize button (down arrow)
- [ ] Verify window closes with animation
- [ ] Confirm floating button reappears
- [ ] Check state is preserved

**Expected Result:** Chat closes and state persists

#### Test 2.3: Multiple Open/Close Cycles
- [ ] Open and close chat 5 times
- [ ] Verify no memory leaks
- [ ] Check animations remain smooth
- [ ] Confirm no console errors

**Expected Result:** Consistent behavior across cycles

### 3. Sending Messages

#### Test 3.1: Send Text Message
- [ ] Open chat window
- [ ] Type "Hello" in input field
- [ ] Click send button
- [ ] Verify message appears in chat
- [ ] Check message is right-aligned
- [ ] Confirm timestamp displays

**Expected Result:** User message sent and displayed correctly

#### Test 3.2: Send with Enter Key
- [ ] Type message
- [ ] Press Enter key
- [ ] Verify message sends
- [ ] Check input clears

**Expected Result:** Enter key sends message

#### Test 3.3: New Line with Shift+Enter
- [ ] Type "Line 1"
- [ ] Press Shift+Enter
- [ ] Type "Line 2"
- [ ] Verify new line created
- [ ] Click send
- [ ] Check both lines display

**Expected Result:** Multi-line message supported

#### Test 3.4: Empty Message Prevention
- [ ] Try to send empty message
- [ ] Verify send button is disabled
- [ ] Type spaces only
- [ ] Confirm send still disabled

**Expected Result:** Cannot send empty messages

#### Test 3.5: Long Message
- [ ] Type message over 500 characters
- [ ] Send message
- [ ] Verify message displays correctly
- [ ] Check text wrapping

**Expected Result:** Long messages handled properly

### 4. Receiving Responses

#### Test 4.1: Bot Response Display
- [ ] Send "What courses do you recommend?"
- [ ] Verify typing indicator appears
- [ ] Wait for response
- [ ] Check bot message is left-aligned
- [ ] Confirm message has white background
- [ ] Verify timestamp displays

**Expected Result:** Bot response received and displayed

#### Test 4.2: Formatted Response
- [ ] Send message that triggers formatted response
- [ ] Verify bold text renders correctly
- [ ] Check bullet points display
- [ ] Confirm line breaks work

**Expected Result:** Markdown formatting applied

#### Test 4.3: Multiple Messages
- [ ] Send 5 different messages
- [ ] Verify all responses received
- [ ] Check messages alternate correctly
- [ ] Confirm scroll works

**Expected Result:** Multiple message exchange works

### 5. Typing Indicator

#### Test 5.1: Typing Animation
- [ ] Send message
- [ ] Observe typing indicator
- [ ] Verify three dots animate
- [ ] Check animation is smooth
- [ ] Confirm indicator disappears after response

**Expected Result:** Typing indicator shows during processing

#### Test 5.2: Header Status
- [ ] Send message
- [ ] Check header shows "Typing..."
- [ ] Wait for response
- [ ] Verify status returns to "Online"

**Expected Result:** Status updates correctly

### 6. Conversation History

#### Test 6.1: Conversation Persistence
- [ ] Send several messages
- [ ] Close chat window
- [ ] Refresh page
- [ ] Open chat window
- [ ] Verify messages are restored

**Expected Result:** Conversation persists across sessions

#### Test 6.2: Scroll to Latest
- [ ] Load conversation with 20+ messages
- [ ] Open chat
- [ ] Verify scrolled to bottom
- [ ] Check latest message visible

**Expected Result:** Auto-scrolls to latest message

#### Test 6.3: Manual Scroll
- [ ] Open chat with many messages
- [ ] Scroll up to view history
- [ ] Send new message
- [ ] Verify doesn't auto-scroll
- [ ] Scroll to bottom manually
- [ ] Send another message
- [ ] Confirm auto-scroll resumes

**Expected Result:** Smart scroll behavior

### 7. New Conversation

#### Test 7.1: Start New Conversation
- [ ] Have active conversation
- [ ] Click "+" button in header
- [ ] Verify confirmation dialog
- [ ] Confirm action
- [ ] Check messages clear
- [ ] Verify welcome message shows

**Expected Result:** New conversation started

#### Test 7.2: Cancel New Conversation
- [ ] Click "+" button
- [ ] Cancel confirmation
- [ ] Verify conversation unchanged
- [ ] Check messages still present

**Expected Result:** Cancellation works

### 8. Error Handling

#### Test 8.1: Network Error
- [ ] Disconnect network
- [ ] Send message
- [ ] Verify error message displays
- [ ] Check error is dismissible
- [ ] Reconnect network
- [ ] Retry sending

**Expected Result:** Error handled gracefully

#### Test 8.2: API Error
- [ ] Trigger API error (invalid data)
- [ ] Verify error message shows
- [ ] Check user can continue
- [ ] Confirm no crash

**Expected Result:** API errors handled

#### Test 8.3: Timeout
- [ ] Send message with slow network
- [ ] Wait for timeout
- [ ] Verify appropriate error
- [ ] Check can retry

**Expected Result:** Timeout handled

### 9. Responsive Design

#### Test 9.1: Desktop View
- [ ] Test on 1920×1080 screen
- [ ] Verify widget positioned correctly
- [ ] Check all elements visible
- [ ] Confirm interactions work

**Expected Result:** Works on desktop

#### Test 9.2: Tablet View
- [ ] Test on 768×1024 screen
- [ ] Verify widget adapts
- [ ] Check touch interactions
- [ ] Confirm readability

**Expected Result:** Works on tablet

#### Test 9.3: Mobile View
- [ ] Test on 375×667 screen
- [ ] Verify widget doesn't overflow
- [ ] Check touch targets adequate
- [ ] Confirm scrolling works

**Expected Result:** Works on mobile

### 10. Accessibility

#### Test 10.1: Keyboard Navigation
- [ ] Tab to chat button
- [ ] Press Enter to open
- [ ] Tab through elements
- [ ] Type message
- [ ] Press Enter to send
- [ ] Verify all actions work

**Expected Result:** Full keyboard support

#### Test 10.2: Screen Reader
- [ ] Enable screen reader
- [ ] Navigate to chat
- [ ] Verify announcements
- [ ] Check labels are read
- [ ] Confirm messages announced

**Expected Result:** Screen reader compatible

#### Test 10.3: Focus Management
- [ ] Open chat
- [ ] Verify input focused
- [ ] Send message
- [ ] Check focus returns to input
- [ ] Close and reopen
- [ ] Confirm focus restored

**Expected Result:** Proper focus management

#### Test 10.4: Color Contrast
- [ ] Check all text contrast ratios
- [ ] Verify meets WCAG AA standards
- [ ] Test in dark mode
- [ ] Confirm readability

**Expected Result:** Sufficient contrast

### 11. Performance

#### Test 11.1: Load Time
- [ ] Measure initial load time
- [ ] Verify under 1 second
- [ ] Check no blocking resources
- [ ] Confirm smooth rendering

**Expected Result:** Fast load time

#### Test 11.2: Message Rendering
- [ ] Send 50 messages
- [ ] Measure rendering time
- [ ] Verify no lag
- [ ] Check smooth scrolling

**Expected Result:** Handles many messages

#### Test 11.3: Memory Usage
- [ ] Open developer tools
- [ ] Monitor memory
- [ ] Send 100 messages
- [ ] Check for memory leaks
- [ ] Close and reopen chat
- [ ] Verify memory released

**Expected Result:** No memory leaks

### 12. Edge Cases

#### Test 12.1: Very Long Message
- [ ] Send 2000 character message
- [ ] Verify displays correctly
- [ ] Check scrolling works
- [ ] Confirm no overflow

**Expected Result:** Long messages handled

#### Test 12.2: Special Characters
- [ ] Send message with emojis 😀
- [ ] Send message with symbols @#$%
- [ ] Send message with HTML <script>
- [ ] Verify all display safely
- [ ] Check no XSS vulnerability

**Expected Result:** Special characters safe

#### Test 12.3: Rapid Messages
- [ ] Send 10 messages quickly
- [ ] Verify all processed
- [ ] Check order maintained
- [ ] Confirm no errors

**Expected Result:** Handles rapid input

#### Test 12.4: Concurrent Users
- [ ] Open chat in two tabs
- [ ] Send messages from both
- [ ] Verify conversations separate
- [ ] Check no interference

**Expected Result:** Isolated conversations

## Automated Testing

### Unit Tests

```javascript
// Test message formatting
test('formats bold text correctly', () => {
    const formatted = formatMessage('**bold**');
    expect(formatted).toContain('<strong>bold</strong>');
});

// Test time formatting
test('formats recent time as "Just now"', () => {
    const now = new Date().toISOString();
    const formatted = formatTime(now);
    expect(formatted).toBe('Just now');
});
```

### Integration Tests

```php
// Test sending message
public function test_user_can_send_chatbot_message()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/chatbot/message', [
            'message' => 'Hello',
        ]);
    
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'conversation_id',
            'message' => ['id', 'content', 'role', 'created_at'],
        ]);
}

// Test conversation loading
public function test_user_can_load_conversation()
{
    $user = User::factory()->create();
    $conversation = ChatbotConversation::factory()
        ->for($user)
        ->create();
    
    $response = $this->actingAs($user)
        ->getJson("/chatbot/conversations/{$conversation->id}");
    
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'conversation',
            'messages',
        ]);
}
```

### E2E Tests

```javascript
// Cypress test
describe('Chatbot Widget', () => {
    it('opens and sends message', () => {
        cy.visit('/dashboard');
        cy.get('[data-testid="chatbot-button"]').click();
        cy.get('[data-testid="message-input"]').type('Hello{enter}');
        cy.get('[data-testid="user-message"]').should('contain', 'Hello');
        cy.get('[data-testid="typing-indicator"]').should('be.visible');
        cy.get('[data-testid="bot-message"]').should('exist');
    });
});
```

## Browser Compatibility Testing

### Chrome/Edge
- [ ] Test on latest version
- [ ] Verify all features work
- [ ] Check animations smooth
- [ ] Confirm no console errors

### Firefox
- [ ] Test on latest version
- [ ] Verify compatibility
- [ ] Check styling consistent
- [ ] Confirm functionality

### Safari
- [ ] Test on latest version
- [ ] Verify iOS compatibility
- [ ] Check touch interactions
- [ ] Confirm animations work

## Performance Benchmarks

| Metric | Target | Actual |
|--------|--------|--------|
| Initial Load | < 1s | ___ |
| Open Animation | < 300ms | ___ |
| Message Send | < 100ms | ___ |
| Bot Response | < 3s | ___ |
| Scroll Performance | 60fps | ___ |
| Memory Usage | < 50MB | ___ |

## Bug Report Template

```markdown
**Title:** [Brief description]

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Behavior:**


**Actual Behavior:**


**Environment:**
- Browser: 
- OS: 
- Screen Size: 

**Screenshots:**


**Console Errors:**

```

## Test Sign-Off

- [ ] All manual tests passed
- [ ] Automated tests passing
- [ ] Performance benchmarks met
- [ ] Accessibility verified
- [ ] Cross-browser tested
- [ ] Mobile tested
- [ ] Documentation reviewed

**Tester:** _______________
**Date:** _______________
**Version:** _______________

## Known Issues

Document any known issues or limitations:

1. 
2. 
3. 

## Future Test Cases

Additional tests to consider:

1. Voice input integration
2. File attachment handling
3. Rich media responses
4. Notification integration
5. Multi-language support
