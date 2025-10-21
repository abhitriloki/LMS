<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FileUploadService
{
    protected string $disk;
    protected VideoProcessingService $videoProcessingService;

    public function __construct(VideoProcessingService $videoProcessingService)
    {
        $this->disk = config('filesystems.default');
        $this->videoProcessingService = $videoProcessingService;
    }

    /**
     * Upload video file with processing
     */
    public function uploadVideo(UploadedFile $file, ?int $lessonId = null): array
    {
        try {
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file, 'video');
            $path = 'courses/videos/' . $filename;

            // Store the video file
            $storedPath = Storage::disk($this->disk)->putFileAs(
                'courses/videos',
                $file,
                $filename
            );

            if (!$storedPath) {
                throw new \Exception('Failed to store video file');
            }

            // Get video metadata
            $metadata = $this->videoProcessingService->getVideoMetadata($storedPath);

            // Generate thumbnail
            $thumbnailPath = $this->videoProcessingService->generateThumbnail($storedPath);

            // Queue video processing for different qualities (optional)
            // This can be done asynchronously via a job
            // dispatch(new ProcessVideoJob($storedPath));

            Log::info('Video uploaded successfully', [
                'path' => $storedPath,
                'lesson_id' => $lessonId,
                'size' => $file->getSize(),
                'duration' => $metadata['duration'] ?? null
            ]);

            return [
                'path' => $storedPath,
                'url' => Storage::disk($this->disk)->url($storedPath),
                'thumbnail_path' => $thumbnailPath,
                'thumbnail_url' => $thumbnailPath ? Storage::disk($this->disk)->url($thumbnailPath) : null,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'metadata' => $metadata,
            ];

        } catch (\Exception $e) {
            Log::error('Video upload failed', [
                'error' => $e->getMessage(),
                'lesson_id' => $lessonId
            ]);
            throw $e;
        }
    }

    /**
     * Upload PDF file
     */
    public function uploadPdf(UploadedFile $file, ?int $lessonId = null): array
    {
        try {
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file, 'pdf');
            $path = 'courses/pdfs/' . $filename;

            // Store the PDF file
            $storedPath = Storage::disk($this->disk)->putFileAs(
                'courses/pdfs',
                $file,
                $filename
            );

            if (!$storedPath) {
                throw new \Exception('Failed to store PDF file');
            }

            // Get PDF metadata (page count, etc.)
            $metadata = $this->getPdfMetadata($storedPath);

            Log::info('PDF uploaded successfully', [
                'path' => $storedPath,
                'lesson_id' => $lessonId,
                'size' => $file->getSize(),
                'pages' => $metadata['pages'] ?? null
            ]);

            return [
                'path' => $storedPath,
                'url' => Storage::disk($this->disk)->url($storedPath),
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'metadata' => $metadata,
            ];

        } catch (\Exception $e) {
            Log::error('PDF upload failed', [
                'error' => $e->getMessage(),
                'lesson_id' => $lessonId
            ]);
            throw $e;
        }
    }

    /**
     * Upload presentation file
     */
    public function uploadPresentation(UploadedFile $file, ?int $lessonId = null): array
    {
        try {
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file, 'presentation');
            $path = 'courses/presentations/' . $filename;

            // Store the presentation file
            $storedPath = Storage::disk($this->disk)->putFileAs(
                'courses/presentations',
                $file,
                $filename
            );

            if (!$storedPath) {
                throw new \Exception('Failed to store presentation file');
            }

            Log::info('Presentation uploaded successfully', [
                'path' => $storedPath,
                'lesson_id' => $lessonId,
                'size' => $file->getSize()
            ]);

            return [
                'path' => $storedPath,
                'url' => Storage::disk($this->disk)->url($storedPath),
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];

        } catch (\Exception $e) {
            Log::error('Presentation upload failed', [
                'error' => $e->getMessage(),
                'lesson_id' => $lessonId
            ]);
            throw $e;
        }
    }

    /**
     * Upload SCORM package
     */
    public function uploadScorm(UploadedFile $file, ?int $lessonId = null): array
    {
        try {
            // Generate unique directory name for SCORM package
            $directoryName = Str::uuid()->toString();
            $path = 'courses/scorm/' . $directoryName;

            // Store the zip file temporarily
            $tempPath = $file->store('temp');

            // Extract SCORM package
            $extractedPath = $this->extractScormPackage($tempPath, $path);

            // Validate SCORM package
            $scormData = $this->validateScormPackage($extractedPath);

            // Delete temporary zip file
            Storage::disk($this->disk)->delete($tempPath);

            Log::info('SCORM package uploaded successfully', [
                'path' => $extractedPath,
                'lesson_id' => $lessonId,
                'size' => $file->getSize()
            ]);

            return [
                'path' => $extractedPath,
                'url' => Storage::disk($this->disk)->url($extractedPath),
                'directory' => $directoryName,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'scorm_data' => $scormData,
            ];

        } catch (\Exception $e) {
            Log::error('SCORM upload failed', [
                'error' => $e->getMessage(),
                'lesson_id' => $lessonId
            ]);
            throw $e;
        }
    }

    /**
     * Delete uploaded file
     */
    public function deleteFile(string $filePath, string $fileType): bool
    {
        try {
            // Delete main file
            if (Storage::disk($this->disk)->exists($filePath)) {
                Storage::disk($this->disk)->delete($filePath);
            }

            // Delete associated files (thumbnails, etc.)
            if ($fileType === 'video') {
                $thumbnailPath = $this->videoProcessingService->getThumbnailPath($filePath);
                if ($thumbnailPath && Storage::disk($this->disk)->exists($thumbnailPath)) {
                    Storage::disk($this->disk)->delete($thumbnailPath);
                }
            }

            // Delete SCORM directory if it's a SCORM package
            if ($fileType === 'scorm') {
                $directory = dirname($filePath);
                if (Storage::disk($this->disk)->exists($directory)) {
                    Storage::disk($this->disk)->deleteDirectory($directory);
                }
            }

            Log::info('File deleted successfully', [
                'path' => $filePath,
                'type' => $fileType
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'error' => $e->getMessage(),
                'path' => $filePath
            ]);
            throw $e;
        }
    }

    /**
     * Get upload progress for chunked uploads
     */
    public function getUploadProgress(string $uploadId): array
    {
        $progress = Cache::get("upload_progress_{$uploadId}", [
            'uploaded' => 0,
            'total' => 0,
            'percentage' => 0,
            'status' => 'pending'
        ]);

        return $progress;
    }

    /**
     * Update upload progress
     */
    public function updateUploadProgress(string $uploadId, int $uploaded, int $total): void
    {
        $percentage = $total > 0 ? round(($uploaded / $total) * 100, 2) : 0;

        Cache::put("upload_progress_{$uploadId}", [
            'uploaded' => $uploaded,
            'total' => $total,
            'percentage' => $percentage,
            'status' => $percentage >= 100 ? 'completed' : 'uploading'
        ], now()->addHours(1));
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file, string $type): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        
        return "{$type}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get PDF metadata
     */
    protected function getPdfMetadata(string $path): array
    {
        $metadata = [
            'pages' => null,
            'size' => null,
        ];

        try {
            $fullPath = Storage::disk($this->disk)->path($path);
            
            if (file_exists($fullPath)) {
                $metadata['size'] = filesize($fullPath);
                
                // Try to get page count using basic PDF parsing
                // For production, consider using a library like TCPDF or PDFParser
                $content = file_get_contents($fullPath);
                if (preg_match_all("/\/Page\W/", $content, $matches)) {
                    $metadata['pages'] = count($matches[0]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract PDF metadata', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
        }

        return $metadata;
    }

    /**
     * Extract SCORM package
     */
    protected function extractScormPackage(string $zipPath, string $extractPath): string
    {
        $zip = new \ZipArchive();
        $fullZipPath = Storage::disk($this->disk)->path($zipPath);
        $fullExtractPath = Storage::disk($this->disk)->path($extractPath);

        if ($zip->open($fullZipPath) === true) {
            // Create extraction directory
            if (!is_dir($fullExtractPath)) {
                mkdir($fullExtractPath, 0755, true);
            }

            $zip->extractTo($fullExtractPath);
            $zip->close();

            return $extractPath;
        }

        throw new \Exception('Failed to extract SCORM package');
    }

    /**
     * Validate SCORM package
     */
    protected function validateScormPackage(string $path): array
    {
        $fullPath = Storage::disk($this->disk)->path($path);
        
        // Look for imsmanifest.xml (SCORM standard)
        $manifestPath = $fullPath . '/imsmanifest.xml';
        
        if (!file_exists($manifestPath)) {
            throw new \Exception('Invalid SCORM package: imsmanifest.xml not found');
        }

        // Parse manifest for basic info
        $xml = simplexml_load_file($manifestPath);
        
        return [
            'version' => (string) $xml->metadata->schemaversion ?? 'unknown',
            'title' => (string) $xml->organizations->organization->title ?? 'Untitled',
            'valid' => true,
        ];
    }
}
