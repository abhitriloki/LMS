# Testing and Quality Assurance - Quick Reference

## Quick Commands

### Run All Tests
```bash
# All tests
php artisan test

# Integration tests only
php artisan test tests/Feature/Integration

# Accessibility tests only
php artisan test tests/Feature/Accessibility
```

### Run Specific Test Suites
```bash
# Complete user workflows
php artisan test tests/Feature/Integration/CompleteUserWorkflowTest

# Course enrollment and completion
php artisan test tests/Feature/Integration/CourseEnrollmentCompletionTest

# Assessment taking
php artisan test tests/Feature/Integration/AssessmentTakingTest

# Keyboard navigation
php artisan test tests/Feature/Accessibility/KeyboardNavigationTest

# Screen reader compatibility
php artisan test tests/Feature/Accessibility/ScreenReaderTest

# Color contrast
php artisan test tests/Feature/Accessibility/ColorContrastTest
```

## Test Coverage

### Integration Tests

#### Complete User Workflows
- ✅ Employee learning journey (registration → certificate)
- ✅ Instructor workflow (course creation → publishing)
- ✅ Admin workflow (user management → analytics)

#### Course Enrollment & Completion
- ✅ Self-enrollment and admin-assigned enrollment
- ✅ Prerequisite validation
- ✅ Mandatory course auto-enrollment
- ✅ Progress tracking
- ✅ Deadline management
- ✅ Bulk enrollment
- ✅ Re-enrollment scenarios

#### Assessment Taking
- ✅ Complete assessment flow
- ✅ Time limits and auto-submit
- ✅ Max attempts enforcement
- ✅ Question randomization
- ✅ Multiple question types
- ✅ Review functionality
- ✅ Pass/fail determination

### Accessibility Tests

#### Keyboard Navigation
- ✅ Form navigation
- ✅ Menu accessibility
- ✅ Modal focus trapping
- ✅ Skip navigation
- ✅ Focus indicators
- ✅ Button types
- ✅ Dropdown menus

#### Screen Reader Compatibility
- ✅ HTML structure
- ✅ Page titles
- ✅ Image alt text
- ✅ Form labels
- ✅ Heading hierarchy
- ✅ ARIA landmarks
- ✅ Link descriptions
- ✅ Button names
- ✅ Table structure
- ✅ Dynamic content announcements

#### Color Contrast
- ✅ Text contrast (4.5:1 minimum)
- ✅ UI component contrast (3:1 minimum)
- ✅ Dark mode support
- ✅ Focus visibility
- ✅ Status indicators
- ✅ Link distinguishability

## Test Files

### Integration Tests
```
tests/Feature/Integration/
├── CompleteUserWorkflowTest.php
├── CourseEnrollmentCompletionTest.php
└── AssessmentTakingTest.php
```

### Accessibility Tests
```
tests/Feature/Accessibility/
├── KeyboardNavigationTest.php
├── ScreenReaderTest.php
└── ColorContrastTest.php
```

## Documentation

### Comprehensive Guides
- **ACCESSIBILITY_TESTING_GUIDE.md** - Complete accessibility testing procedures
- **ACCESSIBILITY_QUICK_REFERENCE.md** - Quick WCAG checklist and fixes
- **TASK_26_TESTING_QA_SUMMARY.md** - Full implementation summary

### Quick Links
- [Full Accessibility Guide](./ACCESSIBILITY_TESTING_GUIDE.md)
- [Accessibility Quick Reference](./ACCESSIBILITY_QUICK_REFERENCE.md)
- [Task 26 Summary](./TASK_26_TESTING_QA_SUMMARY.md)

## WCAG 2.1 AA Checklist

### Perceivable
- [ ] All images have alt text
- [ ] Proper heading hierarchy
- [ ] Text contrast ≥ 4.5:1
- [ ] UI contrast ≥ 3:1

### Operable
- [ ] Keyboard accessible
- [ ] No keyboard trap
- [ ] Skip navigation
- [ ] Visible focus
- [ ] Logical tab order

### Understandable
- [ ] Page language set
- [ ] Clear error messages
- [ ] Form labels present
- [ ] Predictable behavior

### Robust
- [ ] Valid HTML
- [ ] Proper ARIA
- [ ] Status announcements

## Common Test Scenarios

### User Journey Test
```php
// Registration → Enrollment → Content → Assessment → Certificate
test_complete_employee_learning_journey()
```

### Enrollment Test
```php
// Enroll → Track Progress → Complete → Certificate
test_user_can_enroll_complete_course_and_receive_certificate()
```

### Assessment Test
```php
// Start → Answer Questions → Submit → View Results
test_user_can_complete_full_assessment_flow()
```

### Keyboard Test
```php
// Tab through page → Verify focus → Test interactions
test_login_form_has_proper_keyboard_navigation()
```

### Screen Reader Test
```php
// Check structure → Verify labels → Test announcements
test_form_inputs_have_labels()
```

### Contrast Test
```php
// Check colors → Verify ratios → Test dark mode
test_css_file_exists_and_is_accessible()
```

## Testing Tools

### Automated
- **PHPUnit** - Test framework
- **axe DevTools** - Accessibility scanner
- **WAVE** - Visual feedback
- **Lighthouse** - Audits

### Manual
- **NVDA** - Screen reader (Windows)
- **VoiceOver** - Screen reader (macOS)
- **Keyboard** - Navigation testing
- **Contrast Checker** - Color verification

## Quick Fixes

### Missing Alt Text
```html
<img src="image.jpg" alt="Description">
```

### Form Without Label
```html
<label for="email">Email</label>
<input type="email" id="email">
```

### Button Without Text
```html
<button aria-label="Close">
    <i class="icon" aria-hidden="true"></i>
</button>
```

### No Focus Indicator
```css
*:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
```

## Test Execution Workflow

### Before Commit
1. Run relevant test suite
2. Fix any failures
3. Verify changes don't break existing tests

### Before PR
1. Run all integration tests
2. Run accessibility tests for changed features
3. Manual keyboard test
4. Review test coverage

### Before Release
1. Run complete test suite
2. Full accessibility audit
3. Manual testing with screen readers
4. User acceptance testing

## Metrics

- **Integration Test Files**: 3
- **Accessibility Test Files**: 3
- **Total Test Methods**: 50+
- **WCAG Compliance**: Level AA (Target)
- **Test Coverage**: Complete user workflows

## Support

For testing questions:
- Review [ACCESSIBILITY_TESTING_GUIDE.md](./ACCESSIBILITY_TESTING_GUIDE.md)
- Check [ACCESSIBILITY_QUICK_REFERENCE.md](./ACCESSIBILITY_QUICK_REFERENCE.md)
- Consult [TASK_26_TESTING_QA_SUMMARY.md](./TASK_26_TESTING_QA_SUMMARY.md)

---

**Last Updated**: Task 26 Implementation
**Status**: ✅ Complete
