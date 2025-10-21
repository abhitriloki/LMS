# Accessibility Quick Reference

## Quick Testing Commands

```bash
# Run all accessibility tests
php artisan test tests/Feature/Accessibility

# Run keyboard navigation tests
php artisan test tests/Feature/Accessibility/KeyboardNavigationTest

# Run screen reader tests
php artisan test tests/Feature/Accessibility/ScreenReaderTest

# Run color contrast tests
php artisan test tests/Feature/Accessibility/ColorContrastTest
```

## WCAG 2.1 AA Requirements Checklist

### Perceivable

- [ ] **1.1.1** All images have alt text
- [ ] **1.3.1** Proper heading hierarchy (h1 → h2 → h3)
- [ ] **1.3.2** Meaningful sequence of content
- [ ] **1.4.3** Text contrast minimum 4.5:1 (3:1 for large text)
- [ ] **1.4.11** UI component contrast minimum 3:1

### Operable

- [ ] **2.1.1** All functionality available via keyboard
- [ ] **2.1.2** No keyboard trap
- [ ] **2.4.1** Skip navigation link present
- [ ] **2.4.2** Pages have descriptive titles
- [ ] **2.4.3** Logical focus order
- [ ] **2.4.7** Visible focus indicator

### Understandable

- [ ] **3.1.1** Page language specified
- [ ] **3.2.1** Focus doesn't cause unexpected changes
- [ ] **3.2.2** Input doesn't cause unexpected changes
- [ ] **3.3.1** Error messages are clear
- [ ] **3.3.2** Labels provided for inputs
- [ ] **3.3.3** Error suggestions provided

### Robust

- [ ] **4.1.1** Valid HTML
- [ ] **4.1.2** Proper ARIA attributes
- [ ] **4.1.3** Status messages announced

## Essential ARIA Attributes

### Landmarks
```html
<header role="banner">
<nav role="navigation" aria-label="Main">
<main role="main">
<aside role="complementary">
<footer role="contentinfo">
```

### Forms
```html
<input aria-label="Search" aria-required="true">
<input aria-describedby="error-message">
<input aria-invalid="true">
```

### Buttons
```html
<button aria-label="Close">
<button aria-expanded="false" aria-haspopup="true">
<button aria-pressed="false">
```

### Dynamic Content
```html
<div role="alert" aria-live="assertive">
<div role="status" aria-live="polite">
<div aria-atomic="true">
```

### Progress
```html
<div role="progressbar" 
     aria-valuenow="50" 
     aria-valuemin="0" 
     aria-valuemax="100">
```

## Keyboard Shortcuts

| Action | Key |
|--------|-----|
| Navigate forward | Tab |
| Navigate backward | Shift + Tab |
| Activate | Enter or Space |
| Close modal | Escape |
| Dropdown navigation | Arrow keys |

## Color Contrast Requirements

| Content | WCAG AA | WCAG AAA |
|---------|---------|----------|
| Normal text | 4.5:1 | 7:1 |
| Large text (18pt+) | 3:1 | 4.5:1 |
| UI components | 3:1 | - |

## Common Fixes

### Missing Alt Text
```html
<!-- Bad -->
<img src="image.jpg">

<!-- Good -->
<img src="image.jpg" alt="Description">
```

### Form Without Label
```html
<!-- Bad -->
<input type="email" placeholder="Email">

<!-- Good -->
<label for="email">Email</label>
<input type="email" id="email">
```

### Button Without Text
```html
<!-- Bad -->
<button><i class="icon"></i></button>

<!-- Good -->
<button aria-label="Close">
    <i class="icon" aria-hidden="true"></i>
</button>
```

### No Focus Indicator
```css
/* Bad */
*:focus { outline: none; }

/* Good */
*:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
```

### Skipped Heading
```html
<!-- Bad -->
<h1>Title</h1>
<h3>Subtitle</h3>

<!-- Good -->
<h1>Title</h1>
<h2>Subtitle</h2>
```

## Testing Tools

### Browser Extensions
- **axe DevTools** - Automated accessibility testing
- **WAVE** - Visual accessibility feedback
- **Lighthouse** - Built into Chrome DevTools

### Screen Readers
- **NVDA** (Windows) - Free
- **JAWS** (Windows) - Commercial
- **VoiceOver** (macOS) - Built-in

### Contrast Checkers
- WebAIM Contrast Checker
- Chrome DevTools color picker
- Colour Contrast Analyser app

## Quick Test Procedure

1. **Keyboard Test** (5 min)
   - Tab through entire page
   - Verify all interactive elements reachable
   - Check focus indicators visible

2. **Screen Reader Test** (10 min)
   - Enable screen reader
   - Navigate with arrow keys
   - Verify all content announced

3. **Contrast Test** (5 min)
   - Run Lighthouse audit
   - Check text against backgrounds
   - Verify UI component borders

4. **Automated Test** (2 min)
   - Run axe DevTools scan
   - Fix critical issues
   - Document warnings

## Component Checklist

### Forms
- [ ] Labels for all inputs
- [ ] Required fields marked
- [ ] Error messages clear
- [ ] Keyboard accessible

### Navigation
- [ ] Skip link present
- [ ] Logical tab order
- [ ] ARIA labels on menus
- [ ] Keyboard operable

### Modals
- [ ] Focus trapped
- [ ] Escape closes
- [ ] Focus returns on close
- [ ] ARIA role="dialog"

### Tables
- [ ] Header cells marked
- [ ] Caption provided
- [ ] Scope attributes
- [ ] Keyboard navigable

### Images
- [ ] Alt text present
- [ ] Decorative marked
- [ ] Complex images described
- [ ] No text in images

## Resources

- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [WebAIM Checklist](https://webaim.org/standards/wcag/checklist)
- [MDN Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [Full Testing Guide](./ACCESSIBILITY_TESTING_GUIDE.md)
