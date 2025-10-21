<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_content_security_policy_header_is_present(): void
    {
        $response = $this->get('/');

        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        
        $csp = $response->headers->get('Content-Security-Policy');
        
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
    }

    public function test_permissions_policy_header_is_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Permissions-Policy');
    }
}
