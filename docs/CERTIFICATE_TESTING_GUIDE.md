# Certificate System - Testing Guide

## Overview
This guide provides comprehensive testing instructions for the Certificate System implementation.

## Test Files

### Feature Tests
**File:** `tests/Feature/CertificateTest.php`
- Tests complete user workflows
- Tests API endpoints
- Tests access control
- Tests certificate generation and verification

### Unit Tests
**File:** `tests/Unit/Services/CertificateServiceTest.php`
- Tests service methods in isolation
- Tests business logic
- Tests data preparation
- Tests certificate verification logic

## Running Tests

### Run All Certificate Tests
```bash
php artisan test --filter Certificate
```

### Run Feature Tests Only
```bash
php artisan test tests/Feature/CertificateTest.php
```

### Run Unit Tests Only
```bash
php artisan test tests/Unit/Services/CertificateServiceTest.php
```

### Run Specific Test
```bash
php artisan test --filter test_method_name
```

### Run with Coverage
```bash
php artisan test --coverage --filter Certificate
```

## Test Coverage

### Feature Tests (20 tests)

#### Certificate Generation Tests
1. ✓ `it_can_generate_certificate_for_completed_enrollment`
   - Verifies certificate generation for completed courses
   - Checks all required fields are populated

2. ✓ `it_generates_unique_certificate_numbers`
   - Ensures each certificate has a unique number
   - Tests collision prevention

3. ✓ `it_generates_qr_code_for_certificate`
   - Verifies QR code file creation
   - Checks file storage

4. ✓ `it_generates_pdf_for_certificate`
   - Verifies PDF file creation
   - Checks file storage

#### Certificate Verification Tests
5. ✓ `it_can_verify_valid_certificate`
   - Tests verification of valid certificates
   - Checks returned data

6. ✓ `it_returns_null_for_invalid_certificate_number`
   - Tests handling of invalid certificate numbers
   - Verifies null return

#### Bulk Operations Tests
7. ✓ `it_can_generate_certificates_in_bulk`
   - Tests bulk certificate generation
   - Verifies all certificates are created

#### Certificate Management Tests
8. ✓ `it_can_revoke_certificate`
   - Tests certificate revocation
   - Verifies status update

9. ✓ `it_can_regenerate_certificate`
   - Tests certificate regeneration
   - Verifies new files are created

#### User Access Tests
10. ✓ `user_can_view_their_certificates`
    - Tests certificate listing page
    - Verifies user sees their certificates

11. ✓ `user_can_view_certificate_details`
    - Tests certificate detail page
    - Verifies all information is displayed

12. ✓ `user_cannot_view_other_users_certificate`
    - Tests access control
    - Verifies 403 response

13. ✓ `user_can_download_their_certificate`
    - Tests PDF download
    - Verifies content type

#### Public Verification Tests
14. ✓ `anyone_can_verify_certificate`
    - Tests public verification page
    - Verifies certificate details shown

15. ✓ `verification_shows_error_for_invalid_certificate`
    - Tests error handling
    - Verifies error message

#### Admin Tests
16. ✓ `admin_can_view_all_certificates`
    - Tests admin certificate listing
    - Verifies all certificates shown

17. ✓ `admin_can_generate_certificate_for_enrollment`
    - Tests manual certificate generation
    - Verifies database record

18. ✓ `admin_can_bulk_generate_certificates`
    - Tests bulk generation endpoint
    - Verifies multiple certificates created

19. ✓ `admin_can_revoke_certificate`
    - Tests admin revocation
    - Verifies status update

#### Tracking Tests
20. ✓ `it_tracks_certificate_downloads`
    - Tests download counter
    - Verifies timestamp update

21. ✓ `it_identifies_expiring_certificates`
    - Tests expiry detection
    - Verifies filtering logic

### Unit Tests (11 tests)

#### Service Method Tests
1. ✓ `it_generates_unique_certificate_numbers`
   - Tests number generation algorithm
   - Verifies uniqueness

2. ✓ `it_generates_qr_code_for_certificate`
   - Tests QR code generation
   - Verifies file creation

3. ✓ `it_verifies_valid_certificate`
   - Tests verification logic
   - Checks valid certificates

4. ✓ `it_does_not_verify_invalid_certificate`
   - Tests invalid certificate handling
   - Verifies null return

5. ✓ `it_returns_null_for_non_existent_certificate`
   - Tests non-existent certificate handling
   - Verifies null return

6. ✓ `it_revokes_certificate`
   - Tests revocation logic
   - Verifies status change

7. ✓ `it_tracks_certificate_downloads`
   - Tests download tracking
   - Verifies counter increment

8. ✓ `it_gets_expiring_certificates`
   - Tests expiry filtering
   - Verifies correct certificates returned

9. ✓ `it_prepares_certificate_data_correctly`
   - Tests data preparation
   - Verifies all fields present

10. ✓ `it_calculates_expiry_date_from_course_metadata`
    - Tests expiry calculation
    - Verifies date logic

11. ✓ `it_returns_null_expiry_when_no_validity_set`
    - Tests default expiry behavior
    - Verifies null return

## Manual Testing Checklist

### Certificate Generation
- [ ] Complete a course and verify certificate is auto-generated
- [ ] Check certificate PDF is created
- [ ] Verify QR code is generated
- [ ] Confirm email notification is sent
- [ ] Verify certificate appears in user's certificate list

### Certificate Viewing
- [ ] View certificate list as user
- [ ] Click on certificate to view details
- [ ] Verify all information is correct
- [ ] Check QR code is displayed
- [ ] Test download button

### Certificate Download
- [ ] Download certificate PDF
- [ ] Verify PDF opens correctly
- [ ] Check all information is present
- [ ] Verify QR code is in PDF
- [ ] Confirm download counter increments

### Certificate Verification
- [ ] Visit verification page
- [ ] Enter valid certificate number
- [ ] Verify certificate details shown
- [ ] Test with invalid certificate number
- [ ] Scan QR code with mobile device
- [ ] Verify QR code redirects to verification page

### Admin Certificate Management
- [ ] View all certificates as admin
- [ ] Filter certificates by status
- [ ] Search for specific certificate
- [ ] Generate certificate manually
- [ ] Bulk generate certificates
- [ ] Revoke a certificate
- [ ] Restore revoked certificate
- [ ] Regenerate certificate
- [ ] Send certificate via email

### Template Management
- [ ] Create new certificate template
- [ ] Edit existing template
- [ ] Preview template with sample data
- [ ] Set template as default
- [ ] Clone template
- [ ] Delete unused template
- [ ] Verify variables are replaced correctly

### Access Control
- [ ] User can only see their certificates
- [ ] User cannot access other user's certificates
- [ ] Admin can see all certificates
- [ ] Public can verify any certificate
- [ ] Unauthorized users redirected to login

### Email Notifications
- [ ] Certificate email is sent on generation
- [ ] Email contains PDF attachment
- [ ] Email has correct information
- [ ] Resend email works
- [ ] Email tracking is updated

### Expiry Management
- [ ] Create course with certificate expiry
- [ ] Generate certificate
- [ ] Verify expiry date is set
- [ ] Check expiring certificates list
- [ ] Verify expired certificates are marked

## Test Data Setup

### Create Test Users
```php
$user = User::factory()->create();
$admin = User::factory()->create(['role' => 'admin']);
```

### Create Test Course
```php
$course = Course::factory()->create([
    'is_published' => true,
    'metadata' => ['certificate_validity_months' => 12],
]);
```

### Create Test Enrollment
```php
$enrollment = Enrollment::factory()->create([
    'user_id' => $user->id,
    'course_id' => $course->id,
    'status' => 'completed',
    'completion_date' => now(),
    'final_score' => 95.5,
]);
```

### Create Test Template
```php
$template = CertificateTemplate::factory()->create([
    'is_default' => true,
    'is_active' => true,
]);
```

### Create Test Certificate
```php
$certificate = Certificate::factory()->create([
    'user_id' => $user->id,
    'course_id' => $course->id,
    'enrollment_id' => $enrollment->id,
]);
```

## Common Test Scenarios

### Scenario 1: Complete Course and Get Certificate
```php
// 1. Enroll in course
$enrollment = Enrollment::factory()->create([
    'user_id' => $user->id,
    'course_id' => $course->id,
    'status' => 'active',
]);

// 2. Complete all lessons
$course->modules->each(function($module) use ($enrollment) {
    $module->lessons->each(function($lesson) use ($enrollment) {
        LessonProgress::factory()->create([
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
    });
});

// 3. Mark enrollment as completed
$enrollment->update(['status' => 'completed']);

// 4. Verify certificate was generated
$this->assertNotNull($enrollment->fresh()->certificate_id);
```

### Scenario 2: Verify Certificate
```php
// 1. Generate certificate
$certificate = app(CertificateService::class)->generate($enrollment);

// 2. Verify using certificate number
$verified = app(CertificateService::class)->verify($certificate->certificate_number);

// 3. Assert certificate is valid
$this->assertNotNull($verified);
$this->assertEquals($certificate->id, $verified->id);
```

### Scenario 3: Bulk Generate Certificates
```php
// 1. Create multiple completed enrollments
$enrollments = Enrollment::factory()->count(5)->create([
    'status' => 'completed',
    'completion_date' => now(),
]);

// 2. Bulk generate
$certificates = app(CertificateService::class)->generateBulk($enrollments);

// 3. Verify all generated
$this->assertCount(5, $certificates);
```

## Performance Testing

### Test Certificate Generation Speed
```php
$start = microtime(true);
$certificate = app(CertificateService::class)->generate($enrollment);
$duration = microtime(true) - $start;

// Should complete in under 5 seconds
$this->assertLessThan(5, $duration);
```

### Test Bulk Generation Performance
```php
$enrollments = Enrollment::factory()->count(100)->create(['status' => 'completed']);

$start = microtime(true);
$certificates = app(CertificateService::class)->generateBulk($enrollments);
$duration = microtime(true) - $start;

// Should complete in reasonable time
$this->assertLessThan(60, $duration);
```

## Error Handling Tests

### Test Missing Template
```php
CertificateTemplate::query()->delete();

$this->expectException(\Exception::class);
app(CertificateService::class)->generate($enrollment);
```

### Test Invalid Enrollment Status
```php
$enrollment->update(['status' => 'active']);

$response = $this->actingAs($admin)
    ->post(route('admin.certificates.generate', $enrollment));

$response->assertSessionHas('error');
```

### Test Duplicate Certificate Generation
```php
$certificate1 = app(CertificateService::class)->generate($enrollment);
$certificate2 = app(CertificateService::class)->generate($enrollment);

// Should return existing certificate
$this->assertEquals($certificate1->id, $certificate2->id);
```

## Integration Tests

### Test with Queue
```php
Queue::fake();

event(new CourseCompleted($enrollment));

Queue::assertPushed(GenerateCertificateJob::class);
```

### Test with Notifications
```php
Notification::fake();

$certificate = app(CertificateService::class)->generate($enrollment);
app(CertificateService::class)->sendEmail($certificate);

Notification::assertSentTo($user, CertificateIssued::class);
```

### Test with Storage
```php
Storage::fake('public');

$certificate = app(CertificateService::class)->generate($enrollment);

Storage::disk('public')->assertExists($certificate->file_path);
Storage::disk('public')->assertExists($certificate->qr_code_path);
```

## Debugging Failed Tests

### Check Storage
```bash
ls -la storage/app/public/certificates/
ls -la storage/app/public/qr-codes/
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Check Database
```sql
SELECT * FROM certificates ORDER BY created_at DESC LIMIT 10;
SELECT * FROM certificate_templates WHERE is_default = 1;
```

### Check Queue
```bash
php artisan queue:failed
php artisan queue:retry all
```

## Continuous Integration

### GitHub Actions Example
```yaml
- name: Run Certificate Tests
  run: php artisan test --filter Certificate --parallel
```

### Test Coverage Requirements
- Minimum 80% code coverage
- All critical paths tested
- Edge cases covered
- Error handling tested

## Conclusion

This testing guide ensures comprehensive coverage of the Certificate System. Follow these tests to verify all functionality works correctly before deployment.
