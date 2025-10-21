<?php

return [

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for file uploads in the LMS system.
    | All sizes are in kilobytes (KB).
    |
    */

    // Maximum file sizes (in KB)
    'max_video_size' => env('MAX_VIDEO_SIZE', 512000), // 500MB
    'max_pdf_size' => env('MAX_PDF_SIZE', 51200), // 50MB
    'max_presentation_size' => env('MAX_PRESENTATION_SIZE', 51200), // 50MB
    'max_scorm_size' => env('MAX_SCORM_SIZE', 102400), // 100MB
    'max_image_size' => env('MAX_IMAGE_SIZE', 10240), // 10MB

    // Allowed file types
    'allowed_video_types' => ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv'],
    'allowed_pdf_types' => ['pdf'],
    'allowed_presentation_types' => ['ppt', 'pptx', 'odp', 'key'],
    'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],

    // Video processing settings
    'video' => [
        'generate_thumbnail' => env('VIDEO_GENERATE_THUMBNAIL', true),
        'thumbnail_time' => env('VIDEO_THUMBNAIL_TIME', 5), // seconds
        'process_on_upload' => env('VIDEO_PROCESS_ON_UPLOAD', false),
        'quality_presets' => ['low', 'medium', 'high'],
        'default_quality' => env('VIDEO_DEFAULT_QUALITY', 'medium'),
    ],

    // Storage settings
    'storage' => [
        'disk' => env('UPLOAD_DISK', 'public'),
        'video_path' => 'courses/videos',
        'pdf_path' => 'courses/pdfs',
        'presentation_path' => 'courses/presentations',
        'scorm_path' => 'courses/scorm',
        'thumbnail_path' => 'courses/thumbnails',
        'temp_path' => 'temp',
    ],

    // Chunked upload settings
    'chunked_upload' => [
        'enabled' => env('CHUNKED_UPLOAD_ENABLED', true),
        'chunk_size' => env('CHUNKED_UPLOAD_SIZE', 5120), // 5MB chunks
        'max_chunks' => env('CHUNKED_UPLOAD_MAX_CHUNKS', 100),
    ],

    // FFmpeg settings
    'ffmpeg' => [
        'enabled' => env('FFMPEG_ENABLED', true),
        'path' => env('FFMPEG_PATH', 'ffmpeg'),
        'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),
        'timeout' => env('FFMPEG_TIMEOUT', 3600), // 1 hour
    ],

    // SCORM settings
    'scorm' => [
        'validate_manifest' => env('SCORM_VALIDATE_MANIFEST', true),
        'supported_versions' => ['1.2', '2004'],
    ],

];
