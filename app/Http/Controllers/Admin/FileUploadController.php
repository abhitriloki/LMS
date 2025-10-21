<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload video file
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'video' => [
                'required',
                'file',
                'mimes:mp4,mov,avi,wmv,flv,webm',
                'max:512000', // 500MB
            ],
            'lesson_id' => 'nullable|exists:course_lessons,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->fileUploadService->uploadVideo(
                $request->file('video'),
                $request->input('lesson_id')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Video uploaded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Video upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload PDF file
     */
    public function uploadPdf(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pdf' => [
                'required',
                'file',
                'mimes:pdf',
                'max:51200', // 50MB
            ],
            'lesson_id' => 'nullable|exists:course_lessons,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->fileUploadService->uploadPdf(
                $request->file('pdf'),
                $request->input('lesson_id')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'PDF uploaded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('PDF upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload presentation file
     */
    public function uploadPresentation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'presentation' => [
                'required',
                'file',
                'mimes:ppt,pptx,odp',
                'max:51200', // 50MB
            ],
            'lesson_id' => 'nullable|exists:course_lessons,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->fileUploadService->uploadPresentation(
                $request->file('presentation'),
                $request->input('lesson_id')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Presentation uploaded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Presentation upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload presentation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload SCORM package
     */
    public function uploadScorm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'scorm' => [
                'required',
                'file',
                'mimes:zip',
                'max:102400', // 100MB
            ],
            'lesson_id' => 'nullable|exists:course_lessons,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->fileUploadService->uploadScorm(
                $request->file('scorm'),
                $request->input('lesson_id')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'SCORM package uploaded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('SCORM upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload SCORM package: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete uploaded file
     */
    public function deleteFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'file_type' => 'required|in:video,pdf,presentation,scorm',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->fileUploadService->deleteFile(
                $request->input('file_path'),
                $request->input('file_type')
            );

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'error' => $e->getMessage(),
                'file_path' => $request->input('file_path')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upload progress (for chunked uploads)
     */
    public function getUploadProgress(Request $request): JsonResponse
    {
        $uploadId = $request->input('upload_id');
        
        if (!$uploadId) {
            return response()->json([
                'success' => false,
                'message' => 'Upload ID is required'
            ], 422);
        }

        try {
            $progress = $this->fileUploadService->getUploadProgress($uploadId);

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get upload progress'
            ], 500);
        }
    }
}
