<?php

namespace Tests\Unit\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CertificateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = app(CertificateService::class);
    }

    /** @test */
    public function it_generates_unique_certificate_numbers()
    {
        $number1 = $this->service->generateCertificateNumber();
        $number2 = $this->service->generateCertificateNumber();

        $this->assertNotEquals($number1, $number2);
        $this->assertStringStartsWith('CERT-', $number1);
        $this->assertStringStartsWith('CERT-', $number2);
    }

    /** @test */
    public function it_generates_qr_code_for_certificate()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'completed',
        ]);
        $template = CertificateTemplate::factory()->create(['is_default' => true]);

        $certificate = Certificate::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $qrCodePath = $this->service->generateQRCode($certificate);

        $this->assertNotNull($qrCodePath);
        $this->assertStringContains('qr-codes/', $qrCodePath);
        Storage::disk('public')->assertExists($qrCodePath);
    }

    /** @test */
    public function it_verifies_valid_certificate()
    {
        $certificate = Certificate::factory()->create([
            'is_valid' => true,
        ]);

        $verified = $this->service->verify($certificate->certificate_number);

        $this->assertNotNull($verified);
        $this->assertEquals($certificate->id, $verified->id);
    }

    /** @test */
    public function it_does_not_verify_invalid_certificate()
    {
        $certificate = Certificate::factory()->create([
            'is_valid' => false,
        ]);

        $verified = $this->service->verify($certificate->certificate_number);

        $this->assertNull($verified);
    }

    /** @test */
    public function it_returns_null_for_non_existent_certificate()
    {
        $verified = $this->service->verify('NON-EXISTENT-NUMBER');

        $this->assertNull($verified);
    }

    /** @test */
    public function it_revokes_certificate()
    {
        $certificate = Certificate::factory()->create(['is_valid' => true]);
        $reason = 'Test revocation';

        $this->service->revoke($certificate, $reason);

        $certificate->refresh();
        $this->assertFalse($certificate->is_valid);
    }

    /** @test */
    public function it_tracks_certificate_downloads()
    {
        $certificate = Certificate::factory()->create(['download_count' => 0]);

        $this->service->trackDownload($certificate);

        $certificate->refresh();
        $this->assertEquals(1, $certificate->download_count);
        $this->assertNotNull($certificate->last_downloaded_at);
    }

    /** @test */
    public function it_gets_expiring_certificates()
    {
        // Create certificates with different expiry dates
        $expiringSoon = Certificate::factory()->create([
            'expires_at' => now()->addDays(15),
            'is_valid' => true,
        ]);

        $notExpiring = Certificate::factory()->create([
            'expires_at' => now()->addDays(60),
            'is_valid' => true,
        ]);

        $expired = Certificate::factory()->create([
            'expires_at' => now()->subDays(10),
            'is_valid' => true,
        ]);

        $noExpiry = Certificate::factory()->create([
            'expires_at' => null,
            'is_valid' => true,
        ]);

        $expiring = $this->service->getExpiringSoon(30);

        $this->assertTrue($expiring->contains($expiringSoon));
        $this->assertFalse($expiring->contains($notExpiring));
        $this->assertFalse($expiring->contains($expired));
        $this->assertFalse($expiring->contains($noExpiry));
    }

    /** @test */
    public function it_prepares_certificate_data_correctly()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $course = Course::factory()->create(['title' => 'Test Course']);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'completion_date' => now(),
            'final_score' => 95.5,
        ]);

        $certificate = Certificate::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'qr_code_path' => 'qr-codes/test.png',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('prepareCertificateData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service, $certificate);

        $this->assertArrayHasKey('certificate_number', $data);
        $this->assertArrayHasKey('user_name', $data);
        $this->assertArrayHasKey('course_title', $data);
        $this->assertEquals('John Doe', $data['user_name']);
        $this->assertEquals('Test Course', $data['course_title']);
    }

    /** @test */
    public function it_calculates_expiry_date_from_course_metadata()
    {
        $course = Course::factory()->create([
            'metadata' => ['certificate_validity_months' => 12],
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateExpiryDate');
        $method->setAccessible(true);

        $expiryDate = $method->invoke($this->service, $course);

        $this->assertNotNull($expiryDate);
        $this->assertGreaterThan(now(), $expiryDate);
    }

    /** @test */
    public function it_returns_null_expiry_when_no_validity_set()
    {
        $course = Course::factory()->create(['metadata' => []]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateExpiryDate');
        $method->setAccessible(true);

        $expiryDate = $method->invoke($this->service, $course);

        $this->assertNull($expiryDate);
    }
}
