<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    protected CertificateService $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Display a listing of certificates
     */
    public function index(Request $request)
    {
        $query = Certificate::with(['user', 'course', 'template']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('course', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'valid') {
                $query->where('is_valid', true);
            } elseif ($request->status === 'invalid') {
                $query->where('is_valid', false);
            } elseif ($request->status === 'expiring') {
                $query->whereNotNull('expires_at')
                      ->where('expires_at', '>', now())
                      ->where('expires_at', '<=', now()->addDays(30));
            } elseif ($request->status === 'expired') {
                $query->whereNotNull('expires_at')
                      ->where('expires_at', '<=', now());
            }
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->where('issued_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('issued_at', '<=', $request->to_date);
        }

        $certificates = $query->orderBy('issued_at', 'desc')->paginate(20);

        return view('admin.certificates.index', compact('certificates'));
    }

    /**
     * Display the specified certificate
     */
    public function show(Certificate $certificate)
    {
        $certificate->load(['user', 'course', 'enrollment', 'template']);

        return view('admin.certificates.show', compact('certificate'));
    }

    /**
     * Generate certificate for an enrollment
     */
    public function generate(Request $request, Enrollment $enrollment)
    {
        // Check if enrollment is completed
        if ($enrollment->status !== 'completed') {
            return back()->with('error', 'Certificate can only be generated for completed courses.');
        }

        try {
            $certificate = $this->certificateService->generate($enrollment);

            return redirect()
                ->route('admin.certificates.show', $certificate)
                ->with('success', 'Certificate generated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate certificate: ' . $e->getMessage());
        }
    }

    /**
     * Bulk generate certificates
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'enrollment_ids' => 'required|array',
            'enrollment_ids.*' => 'exists:course_enrollments,id',
        ]);

        $enrollments = Enrollment::whereIn('id', $request->enrollment_ids)
            ->where('status', 'completed')
            ->whereNull('certificate_id')
            ->get();

        if ($enrollments->isEmpty()) {
            return back()->with('error', 'No eligible enrollments found for certificate generation.');
        }

        try {
            $certificates = $this->certificateService->generateBulk($enrollments);

            return back()->with('success', "Successfully generated {$certificates->count()} certificates.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate certificates: ' . $e->getMessage());
        }
    }

    /**
     * Regenerate certificate
     */
    public function regenerate(Certificate $certificate)
    {
        try {
            $certificate = $this->certificateService->regenerate($certificate);

            return back()->with('success', 'Certificate regenerated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to regenerate certificate: ' . $e->getMessage());
        }
    }

    /**
     * Revoke certificate
     */
    public function revoke(Request $request, Certificate $certificate)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->certificateService->revoke($certificate, $request->reason);

            return back()->with('success', 'Certificate revoked successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to revoke certificate: ' . $e->getMessage());
        }
    }

    /**
     * Restore revoked certificate
     */
    public function restore(Certificate $certificate)
    {
        $certificate->update(['is_valid' => true]);

        return back()->with('success', 'Certificate restored successfully!');
    }

    /**
     * Send certificate via email
     */
    public function sendEmail(Certificate $certificate)
    {
        try {
            $this->certificateService->sendEmail($certificate);

            return back()->with('success', 'Certificate sent successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send certificate: ' . $e->getMessage());
        }
    }

    /**
     * Show certificates expiring soon
     */
    public function expiring()
    {
        $certificates = $this->certificateService->getExpiringSoon(30);

        return view('admin.certificates.expiring', compact('certificates'));
    }
}
