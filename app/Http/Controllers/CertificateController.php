<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    protected CertificateService $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Display a listing of user's certificates
     */
    public function index(Request $request)
    {
        $certificates = Certificate::with(['course', 'enrollment'])
            ->where('user_id', $request->user()->id)
            ->orderBy('issued_at', 'desc')
            ->paginate(12);

        return view('certificates.index', compact('certificates'));
    }

    /**
     * Display the specified certificate
     */
    public function show(Certificate $certificate)
    {
        // Authorization check
        if ($certificate->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access to certificate');
        }

        $certificate->load(['course', 'user', 'enrollment']);

        return view('certificates.show', compact('certificate'));
    }

    /**
     * Generate certificate for an enrollment
     */
    public function generate(Request $request, Enrollment $enrollment)
    {
        // Authorization check
        $this->authorize('view', $enrollment);

        // Check if enrollment is completed
        if ($enrollment->status !== 'completed') {
            return back()->with('error', 'Certificate can only be generated for completed courses.');
        }

        try {
            $certificate = $this->certificateService->generate($enrollment);

            return redirect()
                ->route('certificates.show', $certificate)
                ->with('success', 'Certificate generated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate certificate: ' . $e->getMessage());
        }
    }

    /**
     * Download certificate PDF
     */
    public function download(Certificate $certificate)
    {
        // Authorization check
        if ($certificate->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access to certificate');
        }

        if (!$certificate->file_path || !Storage::disk('public')->exists($certificate->file_path)) {
            // Attempt on-demand regeneration
            try {
                $certificate = $this->certificateService->regenerate($certificate);
            } catch (\Throwable $e) {
                return back()->with('error', 'Certificate file not found and could not be regenerated: ' . $e->getMessage());
            }

            if (!$certificate->file_path || !Storage::disk('public')->exists($certificate->file_path)) {
                return back()->with('error', 'Certificate file not found.');
            }
        }

        // Track download
        $this->certificateService->trackDownload($certificate);

        return Storage::disk('public')->download(
            $certificate->file_path,
            'Certificate-' . $certificate->certificate_number . '.pdf'
        );
    }

    /**
     * Send certificate via email
     */
    public function email(Certificate $certificate)
    {
        // Authorization check
        if ($certificate->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access to certificate');
        }

        try {
            $this->certificateService->sendEmail($certificate);

            return back()->with('success', 'Certificate sent to your email successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send certificate: ' . $e->getMessage());
        }
    }

    /**
     * Verify certificate by certificate number
     */
    public function verify(Request $request, ?string $certificateNumber = null)
    {
        // If certificate number is provided in URL
        if ($certificateNumber) {
            $certificate = $this->certificateService->verify($certificateNumber);

            if ($certificate) {
                return view('certificates.verify', [
                    'certificate' => $certificate,
                    'verified' => true,
                ]);
            }

            return view('certificates.verify', [
                'certificate' => null,
                'verified' => false,
                'message' => 'Certificate not found or invalid.',
            ]);
        }

        // Show verification form
        return view('certificates.verify', [
            'certificate' => null,
            'verified' => null,
        ]);
    }

    /**
     * Verify certificate via POST request
     */
    public function verifyPost(Request $request)
    {
        $request->validate([
            'certificate_number' => 'required|string',
        ]);

        $certificate = $this->certificateService->verify($request->certificate_number);

        if ($certificate) {
            return view('certificates.verify', [
                'certificate' => $certificate,
                'verified' => true,
            ]);
        }

        return view('certificates.verify', [
            'certificate' => null,
            'verified' => false,
            'message' => 'Certificate not found or invalid.',
        ]);
    }
}
