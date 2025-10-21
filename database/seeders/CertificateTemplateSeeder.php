<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first admin user or create one
        $admin = User::where('role', 'admin')->first() ?? User::first();

        if (!$admin) {
            $this->command->warn('No admin user found. Please create an admin user first.');
            return;
        }

        // Create default certificate template
        CertificateTemplate::create([
            'name' => 'Default Certificate Template',
            'description' => 'Professional certificate template with modern design',
            'html_template' => $this->getDefaultTemplate(),
            'variables' => [
                'certificate_number',
                'user_name',
                'course_title',
                'issued_date',
                'completion_date',
                'final_score',
                'duration',
                'qr_code_url',
                'verification_url',
            ],
            'orientation' => 'landscape',
            'page_size' => 'A4',
            'is_default' => true,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->command->info('Default certificate template created successfully!');
    }

    /**
     * Get default certificate HTML template
     */
    protected function getDefaultTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Georgia', serif;
            width: 297mm;
            height: 210mm;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .certificate-container {
            width: 100%;
            height: 100%;
            padding: 40px;
            position: relative;
        }
        .certificate-content {
            width: 100%;
            height: 100%;
            background: white;
            border: 20px solid #f8f9fa;
            box-shadow: 0 0 0 2px #667eea;
            padding: 60px;
            position: relative;
        }
        .certificate-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 10px;
        }
        .certificate-subtitle {
            font-size: 18px;
            color: #666;
            font-style: italic;
        }
        .certificate-body {
            text-align: center;
            margin: 40px 0;
        }
        .recipient-text {
            font-size: 20px;
            color: #666;
            margin-bottom: 15px;
        }
        .recipient-name {
            font-size: 42px;
            font-weight: bold;
            color: #333;
            margin: 20px 0;
            border-bottom: 3px solid #667eea;
            display: inline-block;
            padding-bottom: 10px;
        }
        .completion-text {
            font-size: 18px;
            color: #666;
            margin: 30px 0;
            line-height: 1.8;
        }
        .course-title {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin: 20px 0;
        }
        .certificate-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        .footer-section {
            text-align: center;
            flex: 1;
        }
        .footer-label {
            font-size: 14px;
            color: #999;
            margin-bottom: 5px;
        }
        .footer-value {
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }
        .signature-line {
            width: 200px;
            border-top: 2px solid #333;
            margin: 0 auto 10px;
        }
        .qr-code {
            position: absolute;
            bottom: 60px;
            right: 60px;
            width: 100px;
            height: 100px;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
        }
        .certificate-number {
            position: absolute;
            top: 60px;
            right: 60px;
            font-size: 12px;
            color: #999;
        }
        .decorative-corner {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 3px solid #667eea;
        }
        .corner-tl {
            top: 40px;
            left: 40px;
            border-right: none;
            border-bottom: none;
        }
        .corner-tr {
            top: 40px;
            right: 40px;
            border-left: none;
            border-bottom: none;
        }
        .corner-bl {
            bottom: 40px;
            left: 40px;
            border-right: none;
            border-top: none;
        }
        .corner-br {
            bottom: 40px;
            right: 40px;
            border-left: none;
            border-top: none;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-content">
            <div class="decorative-corner corner-tl"></div>
            <div class="decorative-corner corner-tr"></div>
            <div class="decorative-corner corner-bl"></div>
            <div class="decorative-corner corner-br"></div>
            
            <div class="certificate-number">
                Certificate No: {{certificate_number}}
            </div>
            
            <div class="certificate-header">
                <div class="certificate-title">Certificate of Completion</div>
                <div class="certificate-subtitle">This is to certify that</div>
            </div>
            
            <div class="certificate-body">
                <div class="recipient-name">{{user_name}}</div>
                
                <div class="completion-text">
                    has successfully completed the course
                </div>
                
                <div class="course-title">{{course_title}}</div>
                
                <div class="completion-text">
                    with a final score of <strong>{{final_score}}</strong>
                </div>
            </div>
            
            <div class="certificate-footer">
                <div class="footer-section">
                    <div class="footer-label">Completion Date</div>
                    <div class="footer-value">{{completion_date}}</div>
                </div>
                
                <div class="footer-section">
                    <div class="signature-line"></div>
                    <div class="footer-label">Authorized Signature</div>
                </div>
                
                <div class="footer-section">
                    <div class="footer-label">Issue Date</div>
                    <div class="footer-value">{{issued_date}}</div>
                </div>
            </div>
            
            <div class="qr-code">
                <img src="{{qr_code_url}}" alt="QR Code">
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
