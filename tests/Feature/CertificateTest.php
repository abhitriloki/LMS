<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected CertificateService $certificateService;
    protected User $user;
    protected User $admin;
    protected Course $course;
    protected Enrollment $enrollment;
    protected CertificateTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->certificateService = app(CertificateService::class);

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->course = Course::factory()->create(['is_published' => true]);
        
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'completed',
            'completion_date' => now(),
            'final_score' => 95.5,
        ]);

        $this->template = CertificateTemplate::factory()->create([
            'is_default' => true,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function it_can_generate_certificate_for_completed_enrollment()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertEquals($this->enrollment->id, $certificate->enrollment_id);
        $this->assertEquals($this->user->id, $certificate->user_id);
        $this->assertEquals($this->course->id, $certificate->course_id);
        $this->assertNotNull($certificate->certificate_number);
        $this->assertTrue($certificate->isValid());
    }

    /** @test */
    public function it_generates_unique_certificate_numbers()
    {
        $certificate1 = $this->certificateService->generate($this->enrollment);
        
        $enrollment2 = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'completed',
        ]);
        
        $certificate2 = $this->certificateService->generate($enrollment2);

        $this->assertNotEquals($certificate1->certificate_number, $certificate2->certificate_number);
    }

    /** @test */
    public function it_generates_qr_code_for_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $this->assertNotNull($certificate->qr_code_path);
        Storage::disk('public')->assertExists($certificate->qr_code_path);
    }

    /** @test */
    public function it_generates_pdf_for_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $this->assertNotNull($certificate->file_path);
        Storage::disk('public')->assertExists($certificate->file_path);
    }

    /** @test */
    public function it_can_verify_valid_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $verified = $this->certificateService->verify($certificate->certificate_number);

        $this->assertNotNull($verified);
        $this->assertEquals($certificate->id, $verified->id);
    }

    /** @test */
    public function it_returns_null_for_invalid_certificate_number()
    {
        $verified = $this->certificateService->verify('INVALID-NUMBER');

        $this->assertNull($verified);
    }

    /** @test */
    public function it_can_generate_certificates_in_bulk()
    {
        $enrollments = Enrollment::factory()->count(3)->create([
            'status' => 'completed',
            'completion_date' => now(),
        ]);

        $certificates = $this->certificateService->generateBulk($enrollments);

        $this->assertCount(3, $certificates);
        foreach ($certificates as $certificate) {
            $this->assertInstanceOf(Certificate::class, $certificate);
            $this->assertNotNull($certificate->certificate_number);
        }
    }

    /** @test */
    public function it_can_revoke_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);
        $reason = 'Course content was updated';

        $this->certificateService->revoke($certificate, $reason);

        $certificate->refresh();
        $this->assertFalse($certificate->is_valid);
    }

    /** @test */
    public function it_can_regenerate_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);
        $oldFilePath = $certificate->file_path;

        $regenerated = $this->certificateService->regenerate($certificate);

        $this->assertNotEquals($oldFilePath, $regenerated->file_path);
        Storage::disk('public')->assertExists($regenerated->file_path);
    }

    /** @test */
    public function user_can_view_their_certificates()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $response = $this->actingAs($this->user)
            ->get(route('certificates.index'));

        $response->assertStatus(200);
        $response->assertSee($certificate->certificate_number);
    }

    /** @test */
    public function user_can_view_certificate_details()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $response = $this->actingAs($this->user)
            ->get(route('certificates.show', $certificate));

        $response->assertStatus(200);
        $response->assertSee($certificate->certificate_number);
        $response->assertSee($this->course->title);
    }

    /** @test */
    public function user_cannot_view_other_users_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('certificates.show', $certificate));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_download_their_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $response = $this->actingAs($this->user)
            ->get(route('certificates.download', $certificate));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function anyone_can_verify_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $response = $this->get(route('certificates.verify', $certificate->certificate_number));

        $response->assertStatus(200);
        $response->assertSee($certificate->certificate_number);
        $response->assertSee('Certificate Verified');
    }

    /** @test */
    public function verification_shows_error_for_invalid_certificate()
    {
        $response = $this->post(route('certificates.verify.post'), [
            'certificate_number' => 'INVALID-NUMBER',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Certificate Not Found');
    }

    /** @test */
    public function admin_can_view_all_certificates()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.certificates.index'));

        $response->assertStatus(200);
        $response->assertSee($certificate->certificate_number);
    }

    /** @test */
    public function admin_can_generate_certificate_for_enrollment()
    {
        $enrollment = Enrollment::factory()->create([
            'status' => 'completed',
            'completion_date' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.certificates.generate', $enrollment));

        $response->assertRedirect();
        $this->assertDatabaseHas('certificates', [
            'enrollment_id' => $enrollment->id,
        ]);
    }

    /** @test */
    public function admin_can_bulk_generate_certificates()
    {
        $enrollments = Enrollment::factory()->count(3)->create([
            'status' => 'completed',
            'completion_date' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.certificates.bulk-generate'), [
                'enrollment_ids' => $enrollments->pluck('id')->toArray(),
            ]);

        $response->assertRedirect();
        $this->assertEquals(3, Certificate::count());
    }

    /** @test */
    public function admin_can_revoke_certificate()
    {
        $certificate = $this->certificateService->generate($this->enrollment);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.certificates.revoke', $certificate), [
                'reason' => 'Test revocation',
            ]);

        $response->assertRedirect();
        $certificate->refresh();
        $this->assertFalse($certificate->is_valid);
    }

    /** @test */
    public function it_tracks_certificate_downloads()
    {
        $certificate = $this->certificateService->generate($this->enrollment);
        $initialCount = $certificate->download_count;

        $this->certificateService->trackDownload($certificate);

        $certificate->refresh();
        $this->assertEquals($initialCount + 1, $certificate->download_count);
        $this->assertNotNull($certificate->last_downloaded_at);
    }

    /** @test */
    public function it_identifies_expiring_certificates()
    {
        $expiringCertificate = Certificate::factory()->create([
            'expires_at' => now()->addDays(15),
            'is_valid' => true,
        ]);

        $validCertificate = Certificate::factory()->create([
            'expires_at' => now()->addDays(60),
            'is_valid' => true,
        ]);

        $expiring = $this->certificateService->getExpiringSoon(30);

        $this->assertTrue($expiring->contains($expiringCertificate));
        $this->assertFalse($expiring->contains($validCertificate));
    }
}
