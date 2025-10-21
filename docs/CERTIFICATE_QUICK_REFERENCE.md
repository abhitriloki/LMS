# Certificate System - Quick Reference

## Service Methods

### CertificateService

```php
// Generate certificate
$certificate = $certificateService->generate($enrollment, $template = null);

// Generate bulk certificates
$certificates = $certificateService->generateBulk($enrollments);

// Generate unique certificate number
$number = $certificateService->generateCertificateNumber();

// Generate QR code
$qrPath = $certificateService->generateQRCode($certificate);

// Generate PDF
$pdfPath = $certificateService->generatePDF($certificate, $template);

// Verify certificate
$certificate = $certificateService->verify($certificateNumber);

// Send email
$certificateService->sendEmail($certificate);

// Track download
$certificateService->trackDownload($certificate);

// Revoke certificate
$certificateService->revoke($certificate, $reason = null);

// Regenerate certificate
$certificate = $certificateService->regenerate($certificate, $template = null);

// Get expiring certificates
$certificates = $certificateService->getExpiringSoon($days = 30);
```

## Model Methods

### Certificate Model

```php
// Check validity
$certificate->isValid();
$certificate->isExpired();

// Get expiry info
$certificate->getDaysUntilExpiration();
$certificate->isExpiringSoon($days = 30);

// Generate certificate number
Certificate::generateCertificateNumber();

// Get URLs
$certificate->getDownloadUrl();
$certificate->getVerificationUrl();

// Query scopes
Certificate::valid()->get();
Certificate::expired()->get();
Certificate::byCertificateNumber($number)->first();
```

### CertificateTemplate Model

```php
// Check status
$template->isDefault();
$template->isActive();

// Set as default
$template->setAsDefault();

// Render template
$html = $template->render($data);

// Query scopes
CertificateTemplate::active()->get();
CertificateTemplate::default()->first();
```

## Routes

### User Routes
```
GET    /certificates
GET    /certificates/{certificate}
POST   /enrollments/{enrollment}/certificate
GET    /certificates/{certificate}/download
POST   /certificates/{certificate}/email
```

### Public Routes
```
GET    /verify-certificate
POST   /verify-certificate
GET    /verify-certificate/{certificateNumber}
```

### Admin Routes
```
GET    /admin/certificates
GET    /admin/certificates/{certificate}
POST   /admin/enrollments/{enrollment}/certificate
POST   /admin/certificates/bulk-generate
POST   /admin/certificates/{certificate}/regenerate
POST   /admin/certificates/{certificate}/revoke
POST   /admin/certificates/{certificate}/restore
POST   /admin/certificates/{certificate}/email
GET    /admin/certificates/expiring/list

GET    /admin/certificate-templates
POST   /admin/certificate-templates
GET    /admin/certificate-templates/{template}
GET    /admin/certificate-templates/{template}/edit
PUT    /admin/certificate-templates/{template}
DELETE /admin/certificate-templates/{template}
POST   /admin/certificate-templates/{template}/set-default
GET    /admin/certificate-templates/{template}/preview
POST   /admin/certificate-templates/{template}/clone
```

## Events & Listeners

```php
// Dispatch course completion event
event(new CourseCompleted($enrollment));

// Event is automatically dispatched when:
// - Enrollment status is set to 'completed'
// - Course progress reaches 100%
```

## Jobs

```php
// Dispatch certificate generation job
GenerateCertificateJob::dispatch($enrollment);
```

## Notifications

```php
// Send certificate issued notification
$user->notify(new CertificateIssued($certificate));
```

## Template Variables

Available variables for certificate templates:
```
{{certificate_number}}
{{user_name}}
{{user_email}}
{{course_title}}
{{course_description}}
{{issued_date}}
{{completion_date}}
{{final_score}}
{{duration}}
{{qr_code_url}}
{{verification_url}}
{{expires_at}}
```

## Database Queries

```php
// Get user's certificates
Certificate::where('user_id', $userId)->get();

// Get valid certificates
Certificate::where('is_valid', true)->get();

// Get expiring certificates
Certificate::whereNotNull('expires_at')
    ->where('expires_at', '>', now())
    ->where('expires_at', '<=', now()->addDays(30))
    ->get();

// Get certificates by course
Certificate::where('course_id', $courseId)->get();

// Search certificates
Certificate::where('certificate_number', 'like', "%{$search}%")
    ->orWhereHas('user', function($q) use ($search) {
        $q->where('name', 'like', "%{$search}%");
    })
    ->get();
```

## Configuration

### Course Metadata for Certificate Expiry
```php
$course->metadata = [
    'certificate_validity_months' => 12, // Certificate expires in 12 months
    'issue_certificate' => true, // Enable/disable certificate issuance
];
```

### Storage Paths
```
storage/app/public/certificates/  - PDF files
storage/app/public/qr-codes/      - QR code images
```

## Common Tasks

### Generate Certificate Manually
```php
$enrollment = Enrollment::find($id);
$certificate = app(CertificateService::class)->generate($enrollment);
```

### Bulk Generate for Course
```php
$enrollments = Enrollment::where('course_id', $courseId)
    ->where('status', 'completed')
    ->whereNull('certificate_id')
    ->get();

$certificates = app(CertificateService::class)->generateBulk($enrollments);
```

### Verify Certificate
```php
$certificate = app(CertificateService::class)->verify($certificateNumber);

if ($certificate) {
    echo "Valid certificate for: " . $certificate->user->name;
} else {
    echo "Invalid or not found";
}
```

### Create Custom Template
```php
CertificateTemplate::create([
    'name' => 'Custom Template',
    'description' => 'My custom certificate design',
    'html_template' => $htmlContent,
    'orientation' => 'landscape',
    'page_size' => 'A4',
    'is_default' => false,
    'is_active' => true,
    'created_by' => auth()->id(),
]);
```

### Revoke Certificate
```php
app(CertificateService::class)->revoke($certificate, 'Course content updated');
```

### Send Certificate Email
```php
app(CertificateService::class)->sendEmail($certificate);
```

## Testing

### Create Test Certificate
```php
$certificate = Certificate::factory()->create();
```

### Create Test Template
```php
$template = CertificateTemplate::factory()->default()->create();
```

### Test Certificate Generation
```php
$enrollment = Enrollment::factory()->create(['status' => 'completed']);
$certificate = app(CertificateService::class)->generate($enrollment);
$this->assertNotNull($certificate->certificate_number);
```

## Artisan Commands

```bash
# Seed default template
php artisan db:seed --class=CertificateTemplateSeeder

# Run certificate tests
php artisan test --filter Certificate

# Clear certificate cache (if implemented)
php artisan cache:clear
```

## Troubleshooting

### Certificate Not Generated
1. Check enrollment status is 'completed'
2. Verify queue workers are running
3. Check default template exists
4. Review logs for errors

### PDF Generation Failed
1. Verify DomPDF is installed
2. Check storage permissions
3. Validate template HTML
4. Check memory limits

### QR Code Not Showing
1. Verify SimpleSoftwareIO QR Code is installed
2. Check storage link exists: `php artisan storage:link`
3. Verify file permissions

### Email Not Sent
1. Check mail configuration
2. Verify queue is processing
3. Check notification settings
4. Review mail logs

## Best Practices

1. Always use queues for certificate generation
2. Validate enrollment completion before generating
3. Use default template as fallback
4. Track certificate downloads for analytics
5. Implement certificate expiry for compliance courses
6. Regular backup of certificate files
7. Monitor expiring certificates
8. Test templates before setting as default
9. Use meaningful certificate numbers
10. Keep audit trail of revocations
