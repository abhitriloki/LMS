<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['super_admin', 'hr_admin', 'instructor']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $type = $this->route('type') ?? $this->input('type');

        return match($type) {
            'video' => $this->videoRules(),
            'pdf' => $this->pdfRules(),
            'presentation' => $this->presentationRules(),
            'scorm' => $this->scormRules(),
            default => [],
        };
    }

    /**
     * Video upload validation rules
     */
    protected function videoRules(): array
    {
        return [
            'video' => [
                'required',
                'file',
                'mimes:mp4,mov,avi,wmv,flv,webm,mkv',
                'max:' . config('upload.max_video_size', 512000), // 500MB default
            ],
            'lesson_id' => 'nullable|exists:course_lessons,id',
            'generate_thumbnail' => 'nullable|boolean',
            'thumbnail_time' => 'nullable|integer|min:0',
        ];
    }

    /**
     * PDF upload validation rules
     */
    protected function pdfRules(): array
    {
        return [
            'pdf' => [
                'required',
                'file',
                'mimes:pdf',
                'max:' . config('upload.max_pdf_size', 51200), // 50MB default
            ],
            'lesson_id' => 'nullable|exists:course_lessons,id',
        ];
    }

    /**
     * Presentation upload validation rules
     */
    protected function presentationRules(): array
    {
        return [
            'presentation' => [
                'required',
                'file',
                'mimes:ppt,pptx,odp,key',
                'max:' . config('upload.max_presentation_size', 51200), // 50MB default
            ],
            'lesson_id' => 'nullable|exists:course_lessons,id',
        ];
    }

    /**
     * SCORM package upload validation rules
     */
    protected function scormRules(): array
    {
        return [
            'scorm' => [
                'required',
                'file',
                'mimes:zip',
                'max:' . config('upload.max_scorm_size', 102400), // 100MB default
            ],
            'lesson_id' => 'nullable|exists:course_lessons,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'video.required' => 'Please select a video file to upload.',
            'video.mimes' => 'The video must be a file of type: mp4, mov, avi, wmv, flv, webm, mkv.',
            'video.max' => 'The video file size must not exceed 500MB.',
            
            'pdf.required' => 'Please select a PDF file to upload.',
            'pdf.mimes' => 'The file must be a PDF document.',
            'pdf.max' => 'The PDF file size must not exceed 50MB.',
            
            'presentation.required' => 'Please select a presentation file to upload.',
            'presentation.mimes' => 'The presentation must be a file of type: ppt, pptx, odp, key.',
            'presentation.max' => 'The presentation file size must not exceed 50MB.',
            
            'scorm.required' => 'Please select a SCORM package to upload.',
            'scorm.mimes' => 'The SCORM package must be a ZIP file.',
            'scorm.max' => 'The SCORM package size must not exceed 100MB.',
            
            'lesson_id.exists' => 'The selected lesson does not exist.',
        ];
    }
}
