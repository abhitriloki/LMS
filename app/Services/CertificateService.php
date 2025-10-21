<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    /**
     * Generate a certificate for an enrollment
     */
    public function generate(Enrollment $enrollment, ?CertificateTemplate $template = null): Certificate
    {
        // Check if certificate already exists
        if ($enrollment->certificate_id) {
            return $enrollment->certificate;
        }

        // Get template (use provided or default)
        $template = $template ?? CertificateTemplate::default()->first();
        
        if (!$template) {
            throw new \Exception('No certificate template available');
        }

        // Generate unique certificate number
        $certificateNumber = $this->generateCertificateNumber();

        // Create certificate record
        $certificate = Certificate::create([
            'certificate_number' => $certificateNumber,
            'user_id' => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'enrollment_id' => $enrollment->id,
            'template_id' => $template->id,
            'issued_at' => now(),
            'expires_at' => $this->calculateExpiryDate($enrollment->course),
        ]);

        // Generate QR code
        $qrCodePath = $this->generateQRCode($certificate);
        $certificate->update(['qr_code_path' => $qrCodePath]);

        // Generate PDF
        $pdfPath = $this->generatePDF($certificate, $template);
        $certificate->update(['file_path' => $pdfPath]);

        // Update enrollment with certificate
        $enrollment->update(['certificate_id' => $certificate->id]);

        return $certificate->fresh();
    }

    /**
     * Generate certificates in bulk
     */
    public function generateBulk(Collection $enrollments): Collection
    {
        $certificates = collect();

        foreach ($enrollments as $enrollment) {
            try {
                $certificate = $this->generate($enrollment);
                $certificates->push($certificate);
            } catch (\Exception $e) {
                \Log::error('Failed to generate certificate for enrollment ' . $enrollment->id, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $certificates;
    }

    /**
     * Generate unique certificate number
     */
    public function generateCertificateNumber(): string
    {
        do {
            $number = 'CERT-' . strtoupper(Str::random(8)) . '-' . now()->format('Y') . '-' . rand(1000, 9999);
        } while (Certificate::where('certificate_number', $number)->exists());

        return $number;
    }

    /**
     * Generate QR code for certificate verification
     */
    public function generateQRCode(Certificate $certificate): string
    {
        $verificationUrl = route('certificates.verify', $certificate->certificate_number);
        
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($verificationUrl);

        $filename = 'qr-codes/' . $certificate->certificate_number . '.png';
        Storage::disk('public')->put($filename, $qrCode);

        return $filename;
    }

    /**
     * Generate PDF certificate
     */
    public function generatePDF(Certificate $certificate, CertificateTemplate $template): string
    {
        // Prepare data for template
        $data = $this->prepareCertificateData($certificate);

        // Render HTML from template
        $html = $this->renderTemplate($template, $data);

        // Generate PDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', $template->orientation ?? 'landscape')
            ->setOption('enable-local-file-access', true);

        // Save PDF
        $filename = 'certificates/' . $certificate->certificate_number . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Prepare certificate data for template rendering
     */
    protected function prepareCertificateData(Certificate $certificate): array
    {
        $user = $certificate->user;
        $course = $certificate->course;
        $enrollment = $certificate->enrollment;

        return [
            'certificate_number' => $certificate->certificate_number,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'course_title' => $course->title,
            'course_description' => $course->short_description ?? $course->description,
            'issued_date' => $certificate->issued_at->format('F d, Y'),
            'completion_date' => $enrollment->completion_date?->format('F d, Y'),
            'final_score' => $enrollment->final_score ? number_format($enrollment->final_score, 2) . '%' : 'N/A',
            'duration' => $course->estimated_duration . ' hours',
            'qr_code_url' => Storage::url($certificate->qr_code_path),
            'verification_url' => route('certificates.verify', $certificate->certificate_number),
            'expires_at' => $certificate->expires_at?->format('F d, Y') ?? 'Never',
        ];
    }

    /**
     * Render template with data
     */
    protected function renderTemplate(CertificateTemplate $template, array $data): string
    {
        $html = $template->html_template;

        // Replace variables in template
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        return $html;
    }

    /**
     * Calculate certificate expiry date based on course settings
     */
    protected function calculateExpiryDate(Course $course): ?string
    {
        // Check if course has expiry settings in metadata
        $metadata = $course->metadata ?? [];
        
        if (isset($metadata['certificate_validity_months'])) {
            return now()->addMonths($metadata['certificate_validity_months'])->toDateTimeString();
        }

        // Default: no expiry
        return null;
    }

    /**
     * Verify certificate by certificate number
     */
    public function verify(string $certificateNumber): ?Certificate
    {
        return Certificate::with(['user', 'course'])
            ->where('certificate_number', $certificateNumber)
            ->where('is_valid', true)
            ->first();
    }

    /**
     * Send certificate via email
     */
    public function sendEmail(Certificate $certificate): void
    {
        $user = $certificate->user;
        
        // Send email notification
        $user->notify(new \App\Notifications\CertificateIssued($certificate));

        // Update certificate record
        $certificate->update([
            'is_emailed' => true,
            'emailed_at' => now(),
        ]);
    }

    /**
     * Track certificate download
     */
    public function trackDownload(Certificate $certificate): void
    {
        $certificate->increment('download_count');
        $certificate->update(['last_downloaded_at' => now()]);
    }

    /**
     * Revoke a certificate
     */
    public function revoke(Certificate $certificate, string $reason = null): void
    {
        $certificate->update([
            'is_valid' => false,
            'metadata' => array_merge($certificate->metadata ?? [], [
                'revoked_at' => now()->toDateTimeString(),
                'revoke_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Regenerate certificate (useful after template updates)
     */
    public function regenerate(Certificate $certificate, ?CertificateTemplate $template = null): Certificate
    {
        // Delete old files
        if ($certificate->file_path) {
            Storage::disk('public')->delete($certificate->file_path);
        }
        if ($certificate->qr_code_path) {
            Storage::disk('public')->delete($certificate->qr_code_path);
        }

        // Get template
        $template = $template ?? $certificate->template ?? CertificateTemplate::default()->first();

        // Regenerate QR code
        $qrCodePath = $this->generateQRCode($certificate);
        $certificate->update(['qr_code_path' => $qrCodePath]);

        // Regenerate PDF
        $pdfPath = $this->generatePDF($certificate, $template);
        $certificate->update(['file_path' => $pdfPath]);

        return $certificate->fresh();
    }

    /**
     * Get certificates expiring soon
     */
    public function getExpiringSoon(int $days = 30): Collection
    {
        return Certificate::whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('is_valid', true)
            ->with(['user', 'course'])
            ->get();
    }
}
