# Accessibility Testing Guide

## Overview

This guide provides comprehensive instructions for testing the accessibility of the AI-Powered Corporate LMS. The system aims to meet WCAG 2.1 AA standards to ensure all users, including those with disabilities, can effectively use the platform.

## Table of Contents

1. [Automated Testing](#automated-testing)
2. [Manual Testing](#manual-testing)
3. [Keyboard Navigation Testing](#keyboard-navigation-testing)
4. [Screen Reader Testing](#screen-reader-testing)
5. [Color Contrast Testing](#color-contrast-testing)
6. [Common Issues and Fixes](#common-issues-and-fixes)

## Automated Testing

### Running Accessibility Tests

```bash
# Run all accessibility tests
php artisan test tests/Feature/Accessibility

# Run specific test suites
php artisan test tests/Feature/Accessibility/KeyboardNavigationTest.php
php artisan test tests/Feature/Accessibility/ScreenReaderTest.php
php artisan test tests/Feature/Accessibility/ColorContrastTest.php
```

### Automated Tools

1. **axe DevTools** (Browser Extension)
   - Install: Chrome/Firefox extension
   - Usage: Open DevTools → axe tab → Scan page
   - Checks: WCAG 2.1 violations automatically

2. **WAVE** (Web Accessibility Evaluation Tool)
   - Install: Browser extension or use wave.webaim.org
   - Usage: Navigate to page → Click WAVE icon
   - Provides: Visual feedback on accessibility issues

3. **Lighthouse** (Built into Chrome DevTools)
   - Usage: DevTools → Lighthouse → Run accessibility audit
   - Provides: Accessibility score and recommendations

## Manual Testing

### Testing Checklist

#### Page Structure
- [ ] Page has a descriptive `<title>`
- [ ] HTML has `lang` attribute
- [ ] Proper heading hierarchy (h1 → h2 → h3, no skipping)
- [ ] Landmarks are present (header, nav, main, footer)
- [ ] Skip navigation link exists

#### Forms
- [ ] All inputs have associated labels
- [ ] Required fields are marked with `required` or `aria-required`
- [ ] Error messages are clear and associated with fields
- [ ] Form validation provides helpful feedback
- [ ] Submit buttons are clearly labeled

#### Images and Media
- [ ] All images have descriptive `alt` text
- [ ] Decorative images have empty `alt=""`
- [ ] Videos have captions/transcripts
- [ ] Audio content has transcripts

#### Interactive Elements
- [ ] All interactive elements are keyboard accessible
- [ ] Focus indicators are visible
- [ ] Buttons have descriptive text or `aria-label`
- [ ] Links have descriptive text (not "click here")
- [ ] Modals trap focus and can be closed with Escape

#### Color and Contrast
- [ ] Text has sufficient contrast (4.5:1 for normal, 3:1 for large)
- [ ] Color is not the only means of conveying information
- [ ] Dark mode maintains proper contrast
- [ ] Focus indicators are visible in all themes

## Keyboard Navigation Testing

### Essential Keyboard Shortcuts

| Action | Key |
|--------|-----|
| Navigate forward | Tab |
| Navigate backward | Shift + Tab |
| Activate button/link | Enter or Space |
| Close modal | Escape |
| Select dropdown option | Arrow keys |
| Check/uncheck checkbox | Space |

### Testing Procedure

1. **Tab Through Page**
   ```
   - Start at top of page
   - Press Tab repeatedly
   - Verify focus moves in logical order
   - Ensure all interactive elements are reachable
   - Verify focus indicator is visible
   ```

2. **Test Forms**
   ```
   - Tab to each form field
   - Verify labels are read
   - Fill out form using only keyboard
   - Submit form with Enter key
   - Verify error messages are accessible
   ```

3. **Test Navigation**
   ```
   - Tab to navigation menu
   - Use arrow keys for dropdowns
   - Activate links with Enter
   - Verify skip navigation link works
   ```

4. **Test Modals**
   ```
   - Open modal with keyboard
   - Verify focus moves to modal
   - Tab through modal elements
   - Verify focus is trapped in modal
   - Close with Escape key
   - Verify focus returns to trigger element
   ```

5. **Test Assessment Interface**
   ```
   - Navigate to questions with Tab
   - Select answers with Space/Enter
   - Navigate between questions
   - Submit assessment with keyboard
   ```

### Common Keyboard Issues

| Issue | Fix |
|-------|-----|
| Element not focusable | Add `tabindex="0"` or use semantic HTML |
| Focus order illogical | Reorder DOM elements or use `tabindex` |
| No focus indicator | Add `:focus` styles in CSS |
| Modal doesn't trap focus | Implement focus trap with JavaScript |
| Can't close modal | Add Escape key handler |

## Screen Reader Testing

### Recommended Screen Readers

- **NVDA** (Windows) - Free
- **JAWS** (Windows) - Commercial
- **VoiceOver** (macOS/iOS) - Built-in
- **TalkBack** (Android) - Built-in

### VoiceOver Testing (macOS)

1. **Enable VoiceOver**
   ```
   Cmd + F5 or System Preferences → Accessibility → VoiceOver
   ```

2. **Basic Commands**
   - Navigate: VO + Right/Left Arrow
   - Interact: VO + Shift + Down Arrow
   - Stop interacting: VO + Shift + Up Arrow
   - Read all: VO + A
   - Open rotor: VO + U

3. **Testing Procedure**
   ```
   1. Enable VoiceOver
   2. Navigate through page with VO + Right Arrow
   3. Verify all content is announced
   4. Test form filling
   5. Test navigation menu
   6. Test interactive components
   7. Verify ARIA labels are read correctly
   ```

### NVDA Testing (Windows)

1. **Enable NVDA**
   ```
   Download from nvaccess.org
   Run NVDA
   ```

2. **Basic Commands**
   - Navigate: Down/Up Arrow
   - Read all: Insert + Down Arrow
   - Elements list: Insert + F7
   - Next heading: H
   - Next link: K
   - Next form field: F

3. **Testing Procedure**
   ```
   1. Start NVDA
   2. Navigate with arrow keys
   3. Test heading navigation (H key)
   4. Test link navigation (K key)
   5. Test form navigation (F key)
   6. Verify announcements are clear
   ```

### Screen Reader Testing Checklist

- [ ] Page title is announced
- [ ] Headings are announced with level
- [ ] Links are announced as links
- [ ] Buttons are announced as buttons
- [ ] Form labels are associated with inputs
- [ ] Error messages are announced
- [ ] Dynamic content updates are announced
- [ ] Images have meaningful alt text
- [ ] Tables have proper headers
- [ ] Lists are announced as lists

### ARIA Attributes to Verify

```html
<!-- Landmarks -->
<nav role="navigation" aria-label="Main navigation">
<main role="main">
<aside role="complementary">

<!-- Form fields -->
<input aria-label="Search courses" aria-required="true">
<input aria-describedby="email-error">

<!-- Buttons -->
<button aria-label="Close modal">
<button aria-expanded="false" aria-haspopup="true">

<!-- Dynamic content -->
<div role="alert" aria-live="polite">
<div role="status" aria-live="polite" aria-atomic="true">

<!-- Progress -->
<div role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
```

## Color Contrast Testing

### Tools

1. **WebAIM Contrast Checker**
   - URL: webaim.org/resources/contrastchecker
   - Usage: Enter foreground and background colors
   - Provides: Pass/fail for WCAG AA and AAA

2. **Chrome DevTools**
   - Usage: Inspect element → Styles → Color picker
   - Shows: Contrast ratio and WCAG compliance

3. **Colour Contrast Analyser**
   - Desktop app for Windows/macOS
   - Eyedropper tool for checking any screen element

### WCAG 2.1 Requirements

| Content Type | Level AA | Level AAA |
|--------------|----------|-----------|
| Normal text | 4.5:1 | 7:1 |
| Large text (18pt+) | 3:1 | 4.5:1 |
| UI components | 3:1 | - |
| Graphical objects | 3:1 | - |

### Testing Procedure

1. **Test Text Contrast**
   ```
   1. Identify all text on page
   2. Check contrast against background
   3. Test in light mode
   4. Test in dark mode
   5. Verify minimum 4.5:1 ratio
   ```

2. **Test UI Components**
   ```
   1. Check button borders
   2. Check form field borders
   3. Check focus indicators
   4. Verify minimum 3:1 ratio
   ```

3. **Test Status Indicators**
   ```
   1. Verify success messages (green)
   2. Verify error messages (red)
   3. Verify warning messages (yellow)
   4. Ensure text is readable
   ```

### Common Contrast Issues

| Issue | Fix |
|-------|-----|
| Light gray text on white | Darken text color |
| Yellow text on white | Use darker yellow or add background |
| Thin borders | Increase border width or darken color |
| Disabled buttons | Ensure 3:1 contrast or use other indicators |

### Color Blindness Testing

Test with color blindness simulators:
- **Chrome Extension**: Colorblindly
- **Online Tool**: color-blindness.com/coblis-color-blindness-simulator

Test scenarios:
- [ ] Deuteranopia (red-green)
- [ ] Protanopia (red-green)
- [ ] Tritanopia (blue-yellow)
- [ ] Monochromacy (grayscale)

## Common Issues and Fixes

### Issue: Missing Alt Text

**Problem:**
```html
<img src="course-thumbnail.jpg">
```

**Fix:**
```html
<img src="course-thumbnail.jpg" alt="Introduction to Laravel course thumbnail">
```

### Issue: Form Without Labels

**Problem:**
```html
<input type="email" placeholder="Email">
```

**Fix:**
```html
<label for="email">Email</label>
<input type="email" id="email" placeholder="Email">
```

### Issue: Button Without Text

**Problem:**
```html
<button><i class="icon-close"></i></button>
```

**Fix:**
```html
<button aria-label="Close modal">
    <i class="icon-close" aria-hidden="true"></i>
</button>
```

### Issue: Skipped Heading Levels

**Problem:**
```html
<h1>Dashboard</h1>
<h3>Recent Courses</h3>
```

**Fix:**
```html
<h1>Dashboard</h1>
<h2>Recent Courses</h2>
```

### Issue: No Focus Indicator

**Problem:**
```css
*:focus {
    outline: none;
}
```

**Fix:**
```css
*:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

*:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
```

### Issue: Modal Doesn't Trap Focus

**Problem:**
Modal allows tabbing to background content

**Fix:**
```javascript
// Use Alpine.js x-trap directive
<div x-show="open" x-trap="open">
    <!-- Modal content -->
</div>
```

### Issue: Dynamic Content Not Announced

**Problem:**
Toast notification appears but screen reader doesn't announce it

**Fix:**
```html
<div role="alert" aria-live="polite" aria-atomic="true">
    {{ message }}
</div>
```

## Testing Schedule

### During Development
- Run automated tests on every commit
- Manual keyboard testing for new features
- Screen reader testing for complex components

### Before Release
- Full accessibility audit with all tools
- Complete keyboard navigation test
- Screen reader testing on all major pages
- Color contrast verification
- User testing with people with disabilities

### Ongoing
- Monthly accessibility audits
- User feedback monitoring
- Regular updates to meet new standards

## Resources

### Documentation
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [MDN Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [WebAIM Resources](https://webaim.org/resources/)

### Tools
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE](https://wave.webaim.org/)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [NVDA Screen Reader](https://www.nvaccess.org/)

### Training
- [Web Accessibility by Google (Udacity)](https://www.udacity.com/course/web-accessibility--ud891)
- [WebAIM Training](https://webaim.org/training/)

## Support

For accessibility issues or questions:
- Create an issue in the project repository
- Contact the development team
- Consult with accessibility specialists

## Compliance Statement

This LMS aims to conform to WCAG 2.1 Level AA standards. We are committed to ensuring digital accessibility for people with disabilities and continuously improving the user experience for everyone.

Last Updated: {{ date }}
