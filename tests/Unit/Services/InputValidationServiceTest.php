<?php

namespace Tests\Unit\Services;

use App\Services\InputValidationService;
use Tests\TestCase;

class InputValidationServiceTest extends TestCase
{
    protected InputValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InputValidationService();
    }

    public function test_sanitize_html_removes_dangerous_tags(): void
    {
        $input = '<p>Safe content</p><script>alert("XSS")</script>';
        $result = $this->service->sanitizeHtml($input);

        $this->assertStringContainsString('<p>Safe content</p>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function test_sanitize_html_removes_javascript_protocol(): void
    {
        $input = '<a href="javascript:alert(\'XSS\')">Click me</a>';
        $result = $this->service->sanitizeHtml($input);

        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function test_sanitize_html_removes_event_handlers(): void
    {
        $input = '<div onclick="alert(\'XSS\')">Click me</div>';
        $result = $this->service->sanitizeHtml($input);

        $this->assertStringNotContainsString('onclick', $result);
    }

    public function test_validate_file_upload_accepts_valid_extensions(): void
    {
        $result = $this->service->validateFileUpload('document.pdf', ['pdf', 'doc']);

        $this->assertTrue($result);
    }

    public function test_validate_file_upload_rejects_invalid_extensions(): void
    {
        $result = $this->service->validateFileUpload('malicious.exe', ['pdf', 'doc']);

        $this->assertFalse($result);
    }

    public function test_validate_file_upload_rejects_double_extensions(): void
    {
        $result = $this->service->validateFileUpload('file.php.jpg', ['jpg', 'png']);

        $this->assertFalse($result);
    }

    public function test_sanitize_filename_removes_special_characters(): void
    {
        $result = $this->service->sanitizeFilename('my file!@#$.pdf');

        $this->assertEquals('my_file____.pdf', $result);
    }

    public function test_validate_sql_input_detects_injection_attempts(): void
    {
        $inputs = [
            "1' OR '1'='1",
            "admin'--",
            "1; DROP TABLE users",
            "1 UNION SELECT * FROM users",
        ];

        foreach ($inputs as $input) {
            $result = $this->service->validateSqlInput($input);
            $this->assertFalse($result, "Failed to detect SQL injection: $input");
        }
    }

    public function test_validate_sql_input_accepts_safe_input(): void
    {
        $result = $this->service->validateSqlInput('John Doe');

        $this->assertTrue($result);
    }

    public function test_sanitize_url_validates_proper_urls(): void
    {
        $result = $this->service->sanitizeUrl('https://example.com/path');

        $this->assertEquals('https://example.com/path', $result);
    }

    public function test_sanitize_url_rejects_javascript_protocol(): void
    {
        $result = $this->service->sanitizeUrl('javascript:alert("XSS")');

        $this->assertNull($result);
    }

    public function test_sanitize_url_rejects_invalid_urls(): void
    {
        $result = $this->service->sanitizeUrl('not a url');

        $this->assertNull($result);
    }

    public function test_validate_email_accepts_valid_emails(): void
    {
        $result = $this->service->validateEmail('user@example.com');

        $this->assertTrue($result);
    }

    public function test_validate_email_rejects_invalid_emails(): void
    {
        $result = $this->service->validateEmail('not-an-email');

        $this->assertFalse($result);
    }

    public function test_sanitize_for_display_escapes_html(): void
    {
        $input = '<script>alert("XSS")</script>';
        $result = $this->service->sanitizeForDisplay($input);

        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $result);
    }
}
