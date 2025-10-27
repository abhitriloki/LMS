<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateTemplateController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a listing of certificate templates
     */
    public function index()
    {
        $templates = CertificateTemplate::with('creator')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.certificate-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        return view('admin.certificate-templates.create');
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'html_template' => 'required|string',
            'orientation' => 'required|in:portrait,landscape',
            'page_size' => 'required|in:A4,Letter',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['variables'] = $this->extractVariables($validated['html_template']);

        // If setting as default, remove default from others
        if ($request->boolean('is_default')) {
            CertificateTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        $template = CertificateTemplate::create($validated);

        return redirect()
            ->route('admin.certificate-templates.show', $template)
            ->with('success', 'Certificate template created successfully!');
    }

    /**
     * Display the specified template
     */
    public function show(CertificateTemplate $certificateTemplate)
    {
        $certificateTemplate->load('creator');
        
        // Get sample certificate for preview
        $sampleCertificate = Certificate::with(['user', 'course', 'enrollment'])
            ->where('template_id', $certificateTemplate->id)
            ->first();

        return view('admin.certificate-templates.show', compact('certificateTemplate', 'sampleCertificate'));
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(CertificateTemplate $certificateTemplate)
    {
        return view('admin.certificate-templates.edit', compact('certificateTemplate'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, CertificateTemplate $certificateTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'html_template' => 'required|string',
            'orientation' => 'required|in:portrait,landscape',
            'page_size' => 'required|in:A4,Letter',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['variables'] = $this->extractVariables($validated['html_template']);

        // If setting as default, remove default from others
        if ($request->boolean('is_default') && !$certificateTemplate->is_default) {
            CertificateTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        $certificateTemplate->update($validated);

        return redirect()
            ->route('admin.certificate-templates.show', $certificateTemplate)
            ->with('success', 'Certificate template updated successfully!');
    }

    /**
     * Remove the specified template
     */
    public function destroy(CertificateTemplate $certificateTemplate)
    {
        // Prevent deletion of default template
        if ($certificateTemplate->is_default) {
            return back()->with('error', 'Cannot delete the default template.');
        }

        // Check if template is in use
        $certificatesCount = Certificate::where('template_id', $certificateTemplate->id)->count();
        
        if ($certificatesCount > 0) {
            return back()->with('error', "Cannot delete template. It is used by {$certificatesCount} certificates.");
        }

        $certificateTemplate->delete();

        return redirect()
            ->route('admin.certificate-templates.index')
            ->with('success', 'Certificate template deleted successfully!');
    }

    /**
     * Set template as default
     */
    public function setDefault(CertificateTemplate $certificateTemplate)
    {
        $certificateTemplate->setAsDefault();

        return back()->with('success', 'Template set as default successfully!');
    }

    /**
     * Preview template with sample data
     */
    public function preview(CertificateTemplate $certificateTemplate)
    {
        $sampleData = [
            'certificate_number' => 'CERT-SAMPLE-2024-1234',
            'user_name' => 'John Doe',
            'user_email' => 'john.doe@example.com',
            'course_title' => 'Advanced Web Development',
            'course_description' => 'A comprehensive course on modern web development',
            'issued_date' => now()->format('F d, Y'),
            'completion_date' => now()->subDays(1)->format('F d, Y'),
            'final_score' => '95.50%',
            'duration' => '40 hours',
            'qr_code_url' => asset('images/sample-qr.png'),
            'verification_url' => route('certificates.verify', 'CERT-SAMPLE-2024-1234'),
            'expires_at' => 'Never',
        ];

        $html = $certificateTemplate->render($sampleData);

        return view('admin.certificate-templates.preview', compact('certificateTemplate', 'html'));
    }

    /**
     * Clone template
     */
    public function clone(CertificateTemplate $certificateTemplate)
    {
        $newTemplate = $certificateTemplate->replicate();
        $newTemplate->name = $certificateTemplate->name . ' (Copy)';
        $newTemplate->is_default = false;
        $newTemplate->created_by = auth()->id();
        $newTemplate->save();

        return redirect()
            ->route('admin.certificate-templates.edit', $newTemplate)
            ->with('success', 'Template cloned successfully!');
    }

    /**
     * Extract variables from HTML template
     */
    protected function extractVariables(string $html): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $html, $matches);
        return array_unique($matches[1]);
    }
}
