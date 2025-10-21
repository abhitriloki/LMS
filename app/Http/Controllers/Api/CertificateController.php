<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Display a listing of user's certificates.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);

        $certificates = $request->user()
            ->certificates()
            ->with(['course'])
            ->orderBy('issued_at', 'desc')
            ->paginate($perPage);

        return CertificateResource::collection($certificates);
    }

    /**
     * Display the specified certificate.
     */
    public function show(Certificate $certificate)
    {
        $this->authorize('view', $certificate);

        $certificate->load(['user', 'course']);

        return response()->json([
            'success' => true,
            'data' => new CertificateResource($certificate),
        ]);
    }

    /**
     * Verify a certificate by certificate number.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'certificate_number' => 'required|string',
        ]);

        $certificate = Certificate::where('certificate_number', $request->certificate_number)
            ->with(['user', 'course'])
            ->first();

        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
            ], 404);
        }

        $isValid = !$certificate->expires_at || $certificate->expires_at->isFuture();

        return response()->json([
            'success' => true,
            'data' => [
                'certificate' => new CertificateResource($certificate),
                'is_valid' => $isValid,
            ],
        ]);
    }
}
