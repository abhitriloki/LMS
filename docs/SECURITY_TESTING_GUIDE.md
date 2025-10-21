# Security Testing Guide

## Overview

This guide provides instructions for testing the security features implemented in the AI-Powered Corporate LMS.

## Test Suites

### 1. Rate Limiting Tests

**Location:** `tests/Feature/Security/RateLimitingTest.php`

**Tests:**
- Login rate limiting after max attempts
- Successful login clears rate limit
- Registration rate limiting
- Password reset rate limiting

**Run Tests:**
```bash
php artisan test --filter RateLimitingTest
```

**Manual Testing:**
1. Navigate to `/login`
2. Enter incorrect credentials 5 times
3. Verify 6th attempt shows rate limit error
4. Wait 15 minutes or clear cache
5. Verify login works again

### 2. Security Headers Tests

**Location:** `tests/Feature/Security/SecurityHeadersTest.php`

**Tests:**
- Security headers are present
- Content Security Policy is configured
- Permissions Policy is set

**Run Tests:**
```bash
php artisan test --filter SecurityHeadersTest
```

**Manual Testing:**
```bash
# Check headers
curl -I http://localhost:8000

# Expected headers:
# X-Content-Type-Options: nosniff
# X-Frame-Options: SAMEORIGIN
# X-XSS-Protection: 1; mode=block
# Content-Security-Policy: ...
# Referrer-Policy: strict-origin-when-cross-origin
```

### 3. Input Validation Tests

**Location:** `tests/Unit/Services/InputValidationServiceTest.php`

**Tests:**
- HTML sanitization removes dangerous tags
- JavaScript protocol removal
- Event handler removal
- File upload validation
- SQL injection detection
- URL sanitization
- Email validation

**Run Tests:**
```bash
php artisan test --filter InputValidationServiceTest
```

**Manual Testing:**
```php
php artisan tinker

use App\Services\InputValidationService;
$service = app(InputValidationService::class);

// Test XSS prevention
$service->sanitizeHtml('<script>alert("XSS")</script>');
// Should return empty or safe content

// Test SQL injection detection
$service->validateSqlInput("1' OR '1'='1");
// Should return false
```

### 4. Password Policy Tests

**Location:** `tests/Unit/Rules/SecurePasswordTest.php`

**Tests:**
- Minimum length requirement
- Uppercase letter requirement
- Lowercase letter requirement
- Number requirement
- Special character requirement
- Common password rejection
- Strong password acceptance

**Run Tests:**
```bash
php artisan test --filter SecurePasswordTest
```

**Manual Testing:**
1. Navigate to `/register`
2. Try passwords:
   - `short` - Should fail (too short)
   - `lowercase123!` - Should fail (no uppercase)
   - `UPPERCASE123!` - Should fail (no lowercase)
   - `NoNumbers!` - Should fail (no numbers)
   - `NoSpecial123` - Should fail (no special chars)
   - `password` - Should fail (too common)
   - `StrongP@ssw0rd!` - Should succeed

### 5. Audit Logging Tests

**Location:** `tests/Feature/Security/AuditLogTest.php`

**Tests:**
- Audit log creation on model creation
- Audit log creation on model update
- Audit log creation on model deletion
- Login logging
- Failed login logging
- Logout logging
- Permission change logging
- Security event logging
- Admin access to audit logs
- Non-admin denied access
- Filtering by event type
- CSV export

**Run Tests:**
```bash
php artisan test --filter AuditLogTest
```

**Manual Testing:**
1. Login as admin
2. Navigate to `/admin/audit-logs`
3. Verify logs are displayed
4. Test filters (event type, date range)
5. Export to CSV
6. Create/update/delete a course
7. Verify new audit logs appear

## Integration Testing

### Test Complete Security Flow

```bash
# 1. Clear cache
php artisan cache:clear

# 2. Run all security tests
php artisan test tests/Feature/Security tests/Unit/Services/InputValidationServiceTest tests/Unit/Rules/SecurePasswordTest

# 3. Check for failures
echo $?
```

### Test Scenarios

#### Scenario 1: Brute Force Attack Prevention

1. Attempt login with wrong password 5 times
2. Verify rate limiting kicks in
3. Check audit logs for failed attempts
4. Verify IP address is logged

**Expected Result:**
- 6th attempt blocked with 429 status
- All attempts logged in audit_logs table
- Clear error message displayed

#### Scenario 2: XSS Attack Prevention

1. Try to submit form with `<script>alert('XSS')</script>`
2. Verify content is sanitized
3. Check stored data doesn't contain script tags

**Expected Result:**
- Script tags removed or escaped
- No JavaScript execution
- Safe content stored in database

#### Scenario 3: SQL Injection Prevention

1. Try to input `1' OR '1'='1` in search field
2. Verify query doesn't execute malicious SQL
3. Check for proper error handling

**Expected Result:**
- Input rejected or sanitized
- No database errors
- Proper validation message

#### Scenario 4: Audit Trail Verification

1. Login as admin
2. Create a new course
3. Update the course
4. Delete the course
5. Check audit logs

**Expected Result:**
- All actions logged with timestamps
- User information captured
- Old and new values recorded
- IP address and user agent logged

## Performance Testing

### Rate Limiting Performance

```bash
# Test rate limiting doesn't impact normal usage
ab -n 100 -c 10 http://localhost:8000/login
```

### Audit Logging Performance

```php
// Test audit logging overhead
php artisan tinker

use App\Models\Course;
use Illuminate\Support\Facades\DB;

// Without audit logging
config(['audit.enabled' => false]);
$start = microtime(true);
Course::factory()->count(100)->create();
$withoutAudit = microtime(true) - $start;

// With audit logging
config(['audit.enabled' => true]);
$start = microtime(true);
Course::factory()->count(100)->create();
$withAudit = microtime(true) - $start;

echo "Without audit: {$withoutAudit}s\n";
echo "With audit: {$withAudit}s\n";
echo "Overhead: " . (($withAudit - $withoutAudit) / $withoutAudit * 100) . "%\n";
```

## Security Scanning

### Static Analysis

```bash
# Install PHPStan
composer require --dev phpstan/phpstan

# Run analysis
./vendor/bin/phpstan analyse app
```

### Dependency Scanning

```bash
# Check for vulnerable dependencies
composer audit
```

### Code Quality

```bash
# Install PHP CS Fixer
composer require --dev friendsofphp/php-cs-fixer

# Check code style
./vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Penetration Testing

### OWASP ZAP

1. Install OWASP ZAP
2. Configure proxy to localhost:8000
3. Run automated scan
4. Review findings
5. Fix vulnerabilities

### Manual Penetration Testing Checklist

- [ ] Test CSRF protection bypass
- [ ] Test XSS in all input fields
- [ ] Test SQL injection in search/filters
- [ ] Test authentication bypass
- [ ] Test authorization bypass
- [ ] Test session hijacking
- [ ] Test file upload vulnerabilities
- [ ] Test rate limiting bypass
- [ ] Test password reset vulnerabilities
- [ ] Test API authentication

## Compliance Testing

### GDPR Compliance

- [ ] Verify IP anonymization works
- [ ] Test data export functionality
- [ ] Test data deletion
- [ ] Verify audit log retention
- [ ] Test consent management

### OWASP Top 10

- [ ] A01:2021 – Broken Access Control
- [ ] A02:2021 – Cryptographic Failures
- [ ] A03:2021 – Injection
- [ ] A04:2021 – Insecure Design
- [ ] A05:2021 – Security Misconfiguration
- [ ] A06:2021 – Vulnerable Components
- [ ] A07:2021 – Authentication Failures
- [ ] A08:2021 – Software and Data Integrity
- [ ] A09:2021 – Security Logging Failures
- [ ] A10:2021 – Server-Side Request Forgery

## Continuous Testing

### CI/CD Integration

Add to `.github/workflows/tests.yml`:

```yaml
name: Security Tests

on: [push, pull_request]

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Dependencies
        run: composer install
      - name: Run Security Tests
        run: php artisan test tests/Feature/Security
      - name: Check Dependencies
        run: composer audit
```

## Reporting

### Test Report Template

```markdown
# Security Test Report

**Date:** [Date]
**Tester:** [Name]
**Version:** [Version]

## Summary
- Total Tests: X
- Passed: X
- Failed: X
- Coverage: X%

## Test Results

### Rate Limiting
- Status: ✅ Pass
- Notes: All rate limiting tests passed

### Security Headers
- Status: ✅ Pass
- Notes: All headers present and configured correctly

### Input Validation
- Status: ✅ Pass
- Notes: XSS and SQL injection prevented

### Password Policy
- Status: ✅ Pass
- Notes: Strong password requirements enforced

### Audit Logging
- Status: ✅ Pass
- Notes: All events logged correctly

## Issues Found
1. [Issue description]
   - Severity: High/Medium/Low
   - Status: Open/Fixed
   - Fix: [Description]

## Recommendations
1. [Recommendation]
2. [Recommendation]

## Next Steps
- [ ] Fix identified issues
- [ ] Re-test failed scenarios
- [ ] Update documentation
```

## Troubleshooting

### Tests Failing

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Refresh database
php artisan migrate:fresh --seed

# Run tests again
php artisan test
```

### Rate Limiting Not Working

```bash
# Check Redis connection
php artisan tinker
>>> Redis::ping()

# Clear rate limit cache
php artisan cache:forget rate_limit:*
```

### Audit Logs Not Created

```bash
# Check configuration
php artisan tinker
>>> config('audit.enabled')

# Check database
>>> \App\Models\AuditLog::count()

# Enable debug mode
>>> config(['audit.enabled' => true])
```

## Resources

- [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [OWASP ZAP User Guide](https://www.zaproxy.org/docs/)
