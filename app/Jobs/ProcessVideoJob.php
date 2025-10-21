<?php

namespace App\Jobs;

use App\Services\VideoProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $videoPath;
    public array $options;
    public int $tries = 3;
    public int $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(string $videoPath, array $options = [])
    {
        $this->videoPath = $videoPath;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(VideoProcessingService $videoProcessingService): void
    {
        try {
            Log::info('Starting video processing', [
                'path' => $this->videoPath,
                'options' => $this->options
            ]);

            // Update processing status
            $this->updateStatus('processing');

            // Generate thumbnail if requested
            if ($this->options['generate_thumbnail'] ?? true) {
                $thumbnailTime = $this->options['thumbnail_time'] ?? 5;
                $thumbnail = $videoProcessingService->generateThumbnail($this->videoPath, $thumbnailTime);
                
                if ($thumbnail) {
                    Log::info('Thumbnail generated', ['path' => $thumbnail]);
                }
            }

            // Convert to different qualities if requested
            if ($this->options['convert_qualities'] ?? false) {
                $qualities = $this->options['qualities'] ?? ['medium'];
                
                foreach ($qualities as $quality) {
                    $convertedPath = $videoProcessingService->convertVideo($this->videoPath, $quality);
                    
                    if ($convertedPath) {
                        Log::info('Video converted', [
                            'quality' => $quality,
                            'path' => $convertedPath
                        ]);
                    }
                }
            }

            // Extract audio if requested
            if ($this->options['extract_audio'] ?? false) {
                $audioPath = $videoProcessingService->extractAudio($this->videoPath);
                
                if ($audioPath) {
                    Log::info('Audio extracted', ['path' => $audioPath]);
                }
            }

            // Update processing status
            $this->updateStatus('completed');

            Log::info('Video processing completed', ['path' => $this->videoPath]);

        } catch (\Exception $e) {
            Log::error('Video processing failed', [
                'path' => $this->videoPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateStatus('failed', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Video processing job failed permanently', [
            'path' => $this->videoPath,
            'error' => $exception->getMessage()
        ]);

        $this->updateStatus('failed', $exception->getMessage());
    }

    /**
     * Update processing status in cache
     */
    protected function updateStatus(string $status, ?string $error = null): void
    {
        $cacheKey = "video_processing_{$this->videoPath}";
        
        Cache::put($cacheKey, [
            'status' => $status,
            'error' => $error,
            'updated_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }
}
