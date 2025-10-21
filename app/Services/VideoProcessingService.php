<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class VideoProcessingService
{
    protected string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default');
    }

    /**
     * Get video metadata using FFmpeg
     */
    public function getVideoMetadata(string $videoPath): array
    {
        $metadata = [
            'duration' => null,
            'width' => null,
            'height' => null,
            'bitrate' => null,
            'codec' => null,
            'fps' => null,
        ];

        try {
            $fullPath = Storage::disk($this->disk)->path($videoPath);

            if (!file_exists($fullPath)) {
                throw new \Exception('Video file not found');
            }

            // Check if FFmpeg is available
            if (!$this->isFFmpegAvailable()) {
                Log::warning('FFmpeg not available, returning basic metadata');
                return $this->getBasicVideoMetadata($fullPath);
            }

            // Use FFprobe to get video metadata
            $command = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams "%s"',
                $fullPath
            );

            $result = Process::run($command);

            if ($result->successful()) {
                $data = json_decode($result->output(), true);
                
                if (isset($data['format'])) {
                    $metadata['duration'] = (float) ($data['format']['duration'] ?? 0);
                    $metadata['bitrate'] = (int) ($data['format']['bit_rate'] ?? 0);
                }

                // Get video stream info
                if (isset($data['streams'])) {
                    foreach ($data['streams'] as $stream) {
                        if ($stream['codec_type'] === 'video') {
                            $metadata['width'] = (int) ($stream['width'] ?? 0);
                            $metadata['height'] = (int) ($stream['height'] ?? 0);
                            $metadata['codec'] = $stream['codec_name'] ?? null;
                            
                            // Calculate FPS
                            if (isset($stream['r_frame_rate'])) {
                                $fps = explode('/', $stream['r_frame_rate']);
                                if (count($fps) === 2 && $fps[1] > 0) {
                                    $metadata['fps'] = round($fps[0] / $fps[1], 2);
                                }
                            }
                            break;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to get video metadata', [
                'path' => $videoPath,
                'error' => $e->getMessage()
            ]);
        }

        return $metadata;
    }

    /**
     * Generate thumbnail from video
     */
    public function generateThumbnail(string $videoPath, ?int $timeInSeconds = 5): ?string
    {
        try {
            $fullPath = Storage::disk($this->disk)->path($videoPath);

            if (!file_exists($fullPath)) {
                throw new \Exception('Video file not found');
            }

            // Check if FFmpeg is available
            if (!$this->isFFmpegAvailable()) {
                Log::warning('FFmpeg not available, cannot generate thumbnail');
                return null;
            }

            // Generate thumbnail filename
            $thumbnailFilename = pathinfo($videoPath, PATHINFO_FILENAME) . '_thumb.jpg';
            $thumbnailPath = 'courses/thumbnails/' . $thumbnailFilename;
            $fullThumbnailPath = Storage::disk($this->disk)->path($thumbnailPath);

            // Create thumbnails directory if it doesn't exist
            $thumbnailDir = dirname($fullThumbnailPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Generate thumbnail using FFmpeg
            $command = sprintf(
                'ffmpeg -i "%s" -ss %d -vframes 1 -vf "scale=640:-1" "%s" -y',
                $fullPath,
                $timeInSeconds,
                $fullThumbnailPath
            );

            $result = Process::run($command);

            if ($result->successful() && file_exists($fullThumbnailPath)) {
                Log::info('Thumbnail generated successfully', [
                    'video_path' => $videoPath,
                    'thumbnail_path' => $thumbnailPath
                ]);

                return $thumbnailPath;
            }

            throw new \Exception('FFmpeg failed to generate thumbnail');

        } catch (\Exception $e) {
            Log::error('Failed to generate thumbnail', [
                'path' => $videoPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate multiple thumbnails at different timestamps
     */
    public function generateMultipleThumbnails(string $videoPath, array $timestamps = [5, 15, 30]): array
    {
        $thumbnails = [];

        foreach ($timestamps as $time) {
            $thumbnail = $this->generateThumbnail($videoPath, $time);
            if ($thumbnail) {
                $thumbnails[] = $thumbnail;
            }
        }

        return $thumbnails;
    }

    /**
     * Convert video to different quality/format
     */
    public function convertVideo(string $videoPath, string $quality = 'medium'): ?string
    {
        try {
            $fullPath = Storage::disk($this->disk)->path($videoPath);

            if (!file_exists($fullPath)) {
                throw new \Exception('Video file not found');
            }

            if (!$this->isFFmpegAvailable()) {
                Log::warning('FFmpeg not available, cannot convert video');
                return null;
            }

            // Define quality presets
            $presets = [
                'low' => [
                    'bitrate' => '500k',
                    'scale' => '640:-1',
                ],
                'medium' => [
                    'bitrate' => '1000k',
                    'scale' => '1280:-1',
                ],
                'high' => [
                    'bitrate' => '2000k',
                    'scale' => '1920:-1',
                ],
            ];

            $preset = $presets[$quality] ?? $presets['medium'];

            // Generate output filename
            $outputFilename = pathinfo($videoPath, PATHINFO_FILENAME) . "_{$quality}.mp4";
            $outputPath = 'courses/videos/processed/' . $outputFilename;
            $fullOutputPath = Storage::disk($this->disk)->path($outputPath);

            // Create output directory if it doesn't exist
            $outputDir = dirname($fullOutputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Convert video using FFmpeg
            $command = sprintf(
                'ffmpeg -i "%s" -vf "scale=%s" -b:v %s -c:v libx264 -preset fast -c:a aac -b:a 128k "%s" -y',
                $fullPath,
                $preset['scale'],
                $preset['bitrate'],
                $fullOutputPath
            );

            $result = Process::run($command);

            if ($result->successful() && file_exists($fullOutputPath)) {
                Log::info('Video converted successfully', [
                    'original_path' => $videoPath,
                    'output_path' => $outputPath,
                    'quality' => $quality
                ]);

                return $outputPath;
            }

            throw new \Exception('FFmpeg failed to convert video');

        } catch (\Exception $e) {
            Log::error('Failed to convert video', [
                'path' => $videoPath,
                'quality' => $quality,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get thumbnail path for a video
     */
    public function getThumbnailPath(string $videoPath): ?string
    {
        $thumbnailFilename = pathinfo($videoPath, PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbnailPath = 'courses/thumbnails/' . $thumbnailFilename;

        if (Storage::disk($this->disk)->exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        return null;
    }

    /**
     * Check if FFmpeg is available
     */
    protected function isFFmpegAvailable(): bool
    {
        try {
            $result = Process::run('ffmpeg -version');
            return $result->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get basic video metadata without FFmpeg
     */
    protected function getBasicVideoMetadata(string $fullPath): array
    {
        return [
            'duration' => null,
            'width' => null,
            'height' => null,
            'bitrate' => null,
            'codec' => null,
            'fps' => null,
            'size' => filesize($fullPath),
        ];
    }

    /**
     * Extract audio from video
     */
    public function extractAudio(string $videoPath): ?string
    {
        try {
            $fullPath = Storage::disk($this->disk)->path($videoPath);

            if (!file_exists($fullPath)) {
                throw new \Exception('Video file not found');
            }

            if (!$this->isFFmpegAvailable()) {
                Log::warning('FFmpeg not available, cannot extract audio');
                return null;
            }

            // Generate audio filename
            $audioFilename = pathinfo($videoPath, PATHINFO_FILENAME) . '.mp3';
            $audioPath = 'courses/audio/' . $audioFilename;
            $fullAudioPath = Storage::disk($this->disk)->path($audioPath);

            // Create audio directory if it doesn't exist
            $audioDir = dirname($fullAudioPath);
            if (!is_dir($audioDir)) {
                mkdir($audioDir, 0755, true);
            }

            // Extract audio using FFmpeg
            $command = sprintf(
                'ffmpeg -i "%s" -vn -acodec libmp3lame -q:a 2 "%s" -y',
                $fullPath,
                $fullAudioPath
            );

            $result = Process::run($command);

            if ($result->successful() && file_exists($fullAudioPath)) {
                Log::info('Audio extracted successfully', [
                    'video_path' => $videoPath,
                    'audio_path' => $audioPath
                ]);

                return $audioPath;
            }

            throw new \Exception('FFmpeg failed to extract audio');

        } catch (\Exception $e) {
            Log::error('Failed to extract audio', [
                'path' => $videoPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
