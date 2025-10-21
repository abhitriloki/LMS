# Security Hardening - Quick Reference

## Quick Start

### 1. Enable Security Features

Add to `.env`:
```env
AUTH_MAX_ATTEMPTS=5
AUTH_DECAY_MINUTES=15
PASSWORD_MIN_LENGTH=8
AUDIT_ENABLED=true
CSP_ENABLED=true
```

### 2. Add Auditing to Models

```php
use App\Traits\Auditable;

class YourModel extends Model
{
    use Auditable;
}
```

### 3. Use Secure Password Validation

```php
use App\Rules\SecurePassword;

$request->validate([
    'password' => ['required', new SecurePassword(), 'confirmed'],
]);
```

## Common Tasks

### Sanitize User Input

```php
use App\Services\InputValidationService;

$validator = app(InputValidationService::class);

// HTML content
$clean = $validator->sanitizeHtml($userInput);

// For display
$safe = $validator->sanitizeForDisplay($userInput);

// Validate file
$isValid = $validator->validateFileUpload($filename, ['pdf', 'doc']);
```

### Manual Audit Logging

```php
use App\Services\AuditLogService;

$audit = app(AuditLogService::class);

// Log custom event
$audit->log('custom_event', $model, 'Description');

// Log login
$audit->logLogin($user, true);

// Log security event
$audit->logSecurityEvent('type', 'Description', ['data' => 'value']);
```

### View Audit Logs (Admin)

Navigate to: `/admin/audit-logs`

Filter by:
- Event type
- Date range
- User
- Model

Export: Click "Export CSV" button

## Security Checklist

- [x] CSRF protection on all forms
- [x] Rate limiting on authentication
- [x] Input validation and sanitization
- [x] Content Security Policy headers
- [x] Secure password policy
- [x] Audit logging enabled
- [x] Security headers configured
- [x] Role-based access control

## Testing

```bash
# Run all security tests
php artisan test tests/Feature/Security

# Run specific tests
php artisan test --filter RateLimitingTest
php artisan test --filter SecurityHeadersTest
php artisan test --filter AuditLogTest
```

## Troubleshooting

### Rate Limit Issues
```bash
php artisan cache:clear
```

### View Audit Logs
```bash
php artisan tinker
>>> AuditLog::latest()->take(10)->get()
```

### Check Security Headers
```bash
curl -I https://your-domain.com
```

## Key Files

- `app/Services/InputValidationService.php` - Input sanitization
- `app/Services/AuditLogService.php` - Audit logging
- `app/Http/Middleware/SecurityHeaders.php` - Security headers
- `app/Http/Middleware/RateLimitAuthentication.php` - Rate limiting
- `app/Traits/Auditable.php` - Auto-audit trait
- `config/security.php` - Security configuration
- `config/audit.php` - Audit configuration

## Routes

### Admin Routes (Super Admin / HR Admin only)
- `GET /admin/audit-logs` - View all logs
- `GET /admin/audit-logs/{id}` - View log details
- `GET /admin/audit-logs/user/{userId}` - User logs
- `GET /admin/audit-logs/export` - Export CSV

## Configuration Files

- `config/security.php` - Security settings
- `config/audit.php` - Audit logging settings
- `.env` - Environment-specific settings
