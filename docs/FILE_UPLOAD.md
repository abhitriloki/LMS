# File Upload System Documentation

## Overview

The file upload system provides comprehensive functionality for uploading and processing various content types including videos, PDFs, presentations, and SCORM packages. It includes features like video processing, thumbnail generation, file validation, and progress tracking.

## Features

- **Multiple File Type Support**: Video, PDF, Presentation, SCORM
- **Video Processing**: Automatic thumbnail generation, metadata extraction, quality conversion
- **File Validation**: Size limits, MIME type checking, format validation
- **Progress Tracking**: Real-time upload progress for large files
- **Asynchronous Processing**: Queue-based video processing for better performance
- **Storage Flexibility**: Support for local and S3 storage
- **SCORM Support**: Extract and validate SCORM packages

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
# File Upload Configuration
UPLOAD_DISK=public
MAX_VIDEO_SIZE=512000          # 500MB in KB
MAX_PDF_SIZE=51200             # 50MB in KB
MAX_PRESENTATION_SIZE=51200    # 50MB in KB
MAX_SCORM_SIZE=102400          # 100MB in KB

# Video Processing
VIDEO_GENERATE_THUMBNAIL=true
VIDEO_THUMBNAIL_TIME=5         # Seconds into video for thumbnail
VIDEO_PROCESS_ON_UPLOAD=false
VIDEO_DEFAULT_QUALITY=medium

# FFmpeg Configuration
FFMPEG_ENABLED=true
FFMPEG_PATH=ffmpeg
FFPROBE_PATH=ffprobe
FFMPEG_TIMEOUT=3600

# Chunked Upload
CHUNKED_UPLOAD_ENABLED=true
CHUNKED_UPLOAD_SIZE=5120       # 5MB chunks
CHUNKED_UPLOAD_MAX_CHUNKS=100
```

### FFmpeg Installation

For video processing features, FFmpeg must be installed:

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install ffmpeg
```

**macOS:**
```bash
brew install ffmpeg
```

**Windows:**
Download from https://ffmpeg.org/download.html and add to PATH

## Usage

### Controller Usage

#### Upload Video

```php
use App\Services\FileUploadService;

public function uploadVideo(Request $request, FileUploadService $fileUploadService)
{
    $result = $fileUploadService->uploadVideo(
        $request->file('video'),
        $lessonId // optional
    );
    
    // Returns:
    // [
    //     'path' => 'courses/videos/video_20240101120000_abc123.mp4',
    //     'url' => 'http://example.com/storage/courses/videos/...',
    //     'thumbnail_path' => 'courses/thumbnails/video_20240101120000_abc123_thumb.jpg',
    //     'thumbnail_url' => 'http://example.com/storage/courses/thumbnails/...',
    //     'filename' => 'video_20240101120000_abc123.mp4',
    //     'original_name' => 'my-video.mp4',
    //     'size' => 52428800,
    //     'mime_type' => 'video/mp4',
    //     'metadata' => [
    //         'duration' => 120.5,
    //         'width' => 1920,
    //         'height' => 1080,
    //         'bitrate' => 5000000,
    //         'codec' => 'h264',
    //         'fps' => 30.0
    //     ]
    // ]
}
```

#### Upload PDF

```php
$result = $fileUploadService->uploadPdf(
    $request->file('pdf'),
    $lessonId // optional
);
```

#### Upload Presentation

```php
$result = $fileUploadService->uploadPresentation(
    $request->file('presentation'),
    $lessonId // optional
);
```

#### Upload SCORM Package

```php
$result = $fileUploadService->uploadScorm(
    $request->file('scorm'),
    $lessonId // optional
);
```

### API Endpoints

#### Upload Video
```
POST /admin/upload/video
Content-Type: multipart/form-data

Parameters:
- video: file (required)
- lesson_id: integer (optional)
- generate_thumbnail: boolean (optional)
- thumbnail_time: integer (optional)
```

#### Upload PDF
```
POST /admin/upload/pdf
Content-Type: multipart/form-data

Parameters:
- pdf: file (required)
- lesson_id: integer (optional)
```

#### Upload Presentation
```
POST /admin/upload/presentation
Content-Type: multipart/form-data

Parameters:
- presentation: file (required)
- lesson_id: integer (optional)
```

#### Upload SCORM
```
POST /admin/upload/scorm
Content-Type: multipart/form-data

Parameters:
- scorm: file (required)
- lesson_id: integer (optional)
```

#### Delete File
```
DELETE /admin/upload/file

Parameters:
- file_path: string (required)
- file_type: string (required) - video|pdf|presentation|scorm
```

### Blade Component Usage

Use the file upload component in your views:

```blade
<x-file-upload 
    type="video" 
    :lesson-id="$lesson->id"
    label="Upload Video"
/>

<x-file-upload 
    type="pdf" 
    :lesson-id="$lesson->id"
/>

<x-file-upload 
    type="presentation" 
    :lesson-id="$lesson->id"
/>

<x-file-upload 
    type="scorm" 
    :lesson-id="$lesson->id"
/>
```

Listen for upload completion:

```blade
<div x-data @file-uploaded.window="handleUpload($event.detail)">
    <x-file-upload type="video" />
</div>

<script>
function handleUpload(data) {
    console.log('File uploaded:', data);
    // Update UI with uploaded file data
}
</script>
```

## Video Processing

### Automatic Processing

Videos are automatically processed on upload if configured:

```php
// In config/upload.php
'video' => [
    'generate_thumbnail' => true,
    'thumbnail_time' => 5,
    'process_on_upload' => true,
]
```

### Manual Processing

Process videos asynchronously using jobs:

```php
use App\Jobs\ProcessVideoJob;

dispatch(new ProcessVideoJob($videoPath, [
    'generate_thumbnail' => true,
    'thumbnail_time' => 5,
    'convert_qualities' => true,
    'qualities' => ['low', 'medium', 'high'],
    'extract_audio' => false,
]));
```

### Video Processing Service

Direct usage of VideoProcessingService:

```php
use App\Services\VideoProcessingService;

$service = app(VideoProcessingService::class);

// Get video metadata
$metadata = $service->getVideoMetadata($videoPath);

// Generate thumbnail
$thumbnailPath = $service->generateThumbnail($videoPath, $timeInSeconds = 5);

// Generate multiple thumbnails
$thumbnails = $service->generateMultipleThumbnails($videoPath, [5, 15, 30]);

// Convert video quality
$convertedPath = $service->convertVideo($videoPath, 'medium');

// Extract audio
$audioPath = $service->extractAudio($videoPath);
```

## File Validation

### Supported File Types

**Video:**
- mp4, mov, avi, wmv, flv, webm, mkv

**PDF:**
- pdf

**Presentation:**
- ppt, pptx, odp, key

**SCORM:**
- zip (containing imsmanifest.xml)

### Size Limits

Default size limits (configurable):
- Video: 500MB
- PDF: 50MB
- Presentation: 50MB
- SCORM: 100MB

### Custom Validation

Create custom validation rules:

```php
use App\Http\Requests\FileUploadRequest;

class CustomFileUploadRequest extends FileUploadRequest
{
    protected function videoRules(): array
    {
        return array_merge(parent::videoRules(), [
            'video' => ['max:1048576'], // 1GB
        ]);
    }
}
```

## Storage

### Local Storage

Files are stored in `storage/app/public/courses/`:
- Videos: `courses/videos/`
- PDFs: `courses/pdfs/`
- Presentations: `courses/presentations/`
- SCORM: `courses/scorm/`
- Thumbnails: `courses/thumbnails/`

### S3 Storage

Configure S3 in `.env`:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

## Error Handling

The system handles various error scenarios:

- File size exceeds limit
- Invalid file type
- Upload failure
- Processing failure
- Storage failure

Errors are logged and returned in a consistent format:

```json
{
    "success": false,
    "message": "Failed to upload video: File size exceeds maximum allowed size",
    "errors": {
        "video": ["The video file size must not exceed 500MB."]
    }
}
```

## Performance Considerations

### Chunked Uploads

For large files, enable chunked uploads:

```env
CHUNKED_UPLOAD_ENABLED=true
CHUNKED_UPLOAD_SIZE=5120  # 5MB chunks
```

### Asynchronous Processing

Process videos asynchronously to avoid blocking:

```php
// Queue video processing
dispatch(new ProcessVideoJob($videoPath, $options));
```

### Caching

Upload progress is cached for 1 hour:

```php
$progress = Cache::get("upload_progress_{$uploadId}");
```

## Security

### Authorization

Only authorized users can upload files:

```php
// In FileUploadRequest
public function authorize(): bool
{
    return $this->user() && in_array($this->user()->role, [
        'super_admin', 'hr_admin', 'instructor'
    ]);
}
```

### File Validation

All uploads are validated for:
- File type (MIME type)
- File size
- File extension
- Content validation (SCORM manifest, etc.)

### Storage Security

- Private files use signed URLs
- Public files are served through Laravel storage
- S3 files use IAM policies

## Troubleshooting

### FFmpeg Not Found

If FFmpeg is not available:
- Thumbnails won't be generated
- Video metadata will be limited
- Video conversion won't work

Check FFmpeg availability:
```bash
ffmpeg -version
```

### Upload Fails

Check:
- PHP upload limits in `php.ini`
- Web server timeout settings
- Storage permissions
- Disk space

### Processing Fails

Check:
- Queue worker is running
- FFmpeg is installed
- Sufficient disk space
- File permissions

## Testing

Run upload tests:

```bash
php artisan test --filter FileUploadTest
```

Test video processing:

```bash
php artisan test --filter VideoProcessingTest
```

## Future Enhancements

Potential improvements:
- Resumable uploads
- Client-side video compression
- Real-time transcoding
- CDN integration
- Advanced video analytics
- Subtitle support
- Multi-language audio tracks
