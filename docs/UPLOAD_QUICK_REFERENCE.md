# File Upload System - Quick Reference

## Quick Start

### 1. Configuration

Add to `.env`:
```env
UPLOAD_DISK=public
MAX_VIDEO_SIZE=512000
FFMPEG_ENABLED=true
```

### 2. Setup

```bash
# Create storage link
php artisan storage:link

# Start queue worker (for video processing)
php artisan queue:work
```

### 3. Use in Blade

```blade
<x-file-upload type="video" :lesson-id="$lesson->id" />
```

## API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/admin/upload/video` | Upload video |
| POST | `/admin/upload/pdf` | Upload PDF |
| POST | `/admin/upload/presentation` | Upload presentation |
| POST | `/admin/upload/scorm` | Upload SCORM |
| DELETE | `/admin/upload/file` | Delete file |
| GET | `/admin/upload/progress` | Get progress |

## File Types & Limits

| Type | Formats | Max Size | Features |
|------|---------|----------|----------|
| Video | mp4, mov, avi, wmv, flv, webm, mkv | 500MB | Thumbnails, metadata |
| PDF | pdf | 50MB | Page count |
| Presentation | ppt, pptx, odp, key | 50MB | - |
| SCORM | zip | 100MB | Extraction, validation |

## Service Usage

### Upload Video

```php
use App\Services\FileUploadService;

$service = app(FileUploadService::class);
$result = $service->uploadVideo($file, $lessonId);

// Returns:
// [
//     'path' => 'courses/videos/video_123.mp4',
//     'url' => 'http://...',
//     'thumbnail_path' => 'courses/thumbnails/video_123_thumb.jpg',
//     'metadata' => ['duration' => 120, 'width' => 1920, ...]
// ]
```

### Process Video

```php
use App\Services\VideoProcessingService;

$service = app(VideoProcessingService::class);

// Get metadata
$metadata = $service->getVideoMetadata($videoPath);

// Generate thumbnail
$thumbnail = $service->generateThumbnail($videoPath, 5);

// Convert quality
$converted = $service->convertVideo($videoPath, 'medium');

// Extract audio
$audio = $service->extractAudio($videoPath);
```

### Queue Processing

```php
use App\Jobs\ProcessVideoJob;

dispatch(new ProcessVideoJob($videoPath, [
    'generate_thumbnail' => true,
    'convert_qualities' => true,
    'qualities' => ['low', 'medium', 'high'],
]));
```

## Blade Component

### Basic Usage

```blade
<x-file-upload type="video" />
```

### With Options

```blade
<x-file-upload 
    type="video"
    :lesson-id="$lesson->id"
    label="Upload Course Video"
    name="course_video"
/>
```

### Listen for Events

```blade
<div x-data @file-uploaded.window="console.log($event.detail)">
    <x-file-upload type="video" />
</div>
```

## Configuration Options

### config/upload.php

```php
'max_video_size' => 512000,  // 500MB
'max_pdf_size' => 51200,     // 50MB

'video' => [
    'generate_thumbnail' => true,
    'thumbnail_time' => 5,
    'quality_presets' => ['low', 'medium', 'high'],
],

'ffmpeg' => [
    'enabled' => true,
    'path' => 'ffmpeg',
    'timeout' => 3600,
],
```

## Common Tasks

### Upload and Update Lesson

```php
// In controller
$result = $this->fileUploadService->uploadVideo($request->file('video'));

$lesson->update([
    'content_path' => $result['path'],
    'content_type' => 'video',
    'metadata' => $result['metadata'],
]);
```

### Delete File with Cleanup

```php
$this->fileUploadService->deleteFile($filePath, 'video');
// Automatically removes thumbnails and associated files
```

### Track Upload Progress

```php
// Get progress
$progress = $this->fileUploadService->getUploadProgress($uploadId);

// Update progress
$this->fileUploadService->updateUploadProgress($uploadId, $uploaded, $total);
```

## Validation

### In Controller

```php
$request->validate([
    'video' => 'required|file|mimes:mp4,mov|max:512000',
]);
```

### Using Form Request

```php
use App\Http\Requests\FileUploadRequest;

public function upload(FileUploadRequest $request)
{
    // Already validated
}
```

## Error Handling

### Try-Catch Pattern

```php
try {
    $result = $fileUploadService->uploadVideo($file);
    return response()->json(['success' => true, 'data' => $result]);
} catch (\Exception $e) {
    Log::error('Upload failed', ['error' => $e->getMessage()]);
    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
}
```

### Check FFmpeg Availability

```php
if (!$videoProcessingService->isFFmpegAvailable()) {
    // Fallback or warning
}
```

## Testing

### Feature Test Example

```php
public function test_can_upload_video()
{
    Storage::fake('public');
    
    $file = UploadedFile::fake()->create('video.mp4', 10000);
    
    $response = $this->post('/admin/upload/video', [
        'video' => $file,
    ]);
    
    $response->assertSuccessful();
    Storage::disk('public')->assertExists('courses/videos/' . $file->hashName());
}
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| FFmpeg not found | Install: `sudo apt install ffmpeg` |
| Upload fails | Check storage permissions |
| No thumbnails | Verify FFmpeg installation |
| Large file timeout | Increase `max_execution_time` in php.ini |
| Queue not processing | Start worker: `php artisan queue:work` |

## Storage Paths

```
storage/app/public/
├── courses/
│   ├── videos/          # Video files
│   ├── pdfs/            # PDF documents
│   ├── presentations/   # Presentation files
│   ├── scorm/           # SCORM packages
│   ├── thumbnails/      # Video thumbnails
│   └── audio/           # Extracted audio
```

## Environment Variables

```env
# Required
UPLOAD_DISK=public

# Optional (with defaults)
MAX_VIDEO_SIZE=512000
MAX_PDF_SIZE=51200
MAX_PRESENTATION_SIZE=51200
MAX_SCORM_SIZE=102400

VIDEO_GENERATE_THUMBNAIL=true
VIDEO_THUMBNAIL_TIME=5

FFMPEG_ENABLED=true
FFMPEG_PATH=ffmpeg
FFMPEG_TIMEOUT=3600

CHUNKED_UPLOAD_ENABLED=true
CHUNKED_UPLOAD_SIZE=5120
```

## Links

- Full Documentation: `docs/FILE_UPLOAD.md`
- Implementation Summary: `docs/TASK_8.5_SUMMARY.md`
- Configuration: `config/upload.php`
