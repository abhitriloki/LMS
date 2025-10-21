<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class AssetOptimizationService
{
    /**
     * Optimize an uploaded image
     */
    public function optimizeImage(string $path, array $options = []): bool
    {
        try {
            if (!File::exists($path)) {
                return false;
            }

            $maxWidth = $options['max_width'] ?? 1920;
            $maxHeight = $options['max_height'] ?? 1080;
            $quality = $options['quality'] ?? 85;

            // Load image
            $image = Image::make($path);

            // Resize if larger than max dimensions
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Save with compression
            $image->save($path, $quality);

            Log::info('Image optimized', [
                'path' => $path,
                'original_size' => File::size($path),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Image optimization failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate responsive image sizes
     */
    public function generateResponsiveSizes(string $path, array $sizes = []): array
    {
        $defaultSizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 320, 'height' => 240],
            'medium' => ['width' => 640, 'height' => 480],
            'large' => ['width' => 1024, 'height' => 768],
        ];

        $sizes = array_merge($defaultSizes, $sizes);
        $generated = [];

        try {
            $image = Image::make($path);
            $pathInfo = pathinfo($path);

            foreach ($sizes as $name => $dimensions) {
                $newPath = $pathInfo['dirname'] . '/' . 
                          $pathInfo['filename'] . '_' . $name . '.' . 
                          $pathInfo['extension'];

                $resized = clone $image;
                $resized->fit($dimensions['width'], $dimensions['height']);
                $resized->save($newPath, 85);

                $generated[$name] = $newPath;
            }

            return $generated;
        } catch (\Exception $e) {
            Log::error('Responsive image generation failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate WebP version of image
     */
    public function generateWebP(string $path): ?string
    {
        try {
            $pathInfo = pathinfo($path);
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

            $image = Image::make($path);
            $image->encode('webp', 85);
            $image->save($webpPath);

            Log::info('WebP image generated', [
                'original' => $path,
                'webp' => $webpPath,
            ]);

            return $webpPath;
        } catch (\Exception $e) {
            Log::error('WebP generation failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Clean up old cached assets
     */
    public function cleanupOldAssets(int $days = 30): int
    {
        $count = 0;
        $cachePath = storage_path('app/public/cache');

        if (!File::exists($cachePath)) {
            return 0;
        }

        $files = File::allFiles($cachePath);
        $threshold = now()->subDays($days)->timestamp;

        foreach ($files as $file) {
            if ($file->getMTime() < $threshold) {
                File::delete($file->getPathname());
                $count++;
            }
        }

        Log::info('Old assets cleaned up', ['count' => $count]);

        return $count;
    }

    /**
     * Get asset statistics
     */
    public function getAssetStats(): array
    {
        $publicPath = public_path();
        $storagePath = storage_path('app/public');

        return [
            'public_size' => $this->getDirectorySize($publicPath),
            'storage_size' => $this->getDirectorySize($storagePath),
            'total_files' => count(File::allFiles($publicPath)) + count(File::allFiles($storagePath)),
        ];
    }

    /**
     * Get directory size in bytes
     */
    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        if (!File::exists($path)) {
            return 0;
        }

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Format bytes to human readable
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
