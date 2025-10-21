<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    /**
     * Perform health check on all system components.
     */
    public function index(): JsonResponse
    {
        $checks = config('monitoring.health_checks.checks', []);
        $results = [];
        $overallStatus = 'healthy';

        if ($checks['database'] ?? false) {
            $results['database'] = $this->checkDatabase();
            if ($results['database']['status'] !== 'healthy') {
                $overallStatus = 'unhealthy';
            }
        }

        if ($checks['redis'] ?? false) {
            $results['redis'] = $this->checkRedis();
            if ($results['redis']['status'] !== 'healthy') {
                $overallStatus = 'unhealthy';
            }
        }

        if ($checks['storage'] ?? false) {
            $results['storage'] = $this->checkStorage();
            if ($results['storage']['status'] !== 'healthy') {
                $overallStatus = 'unhealthy';
            }
        }

        if ($checks['queue'] ?? false) {
            $results['queue'] = $this->checkQueue();
            if ($results['queue']['status'] !== 'healthy') {
                $overallStatus = 'degraded';
            }
        }

        if ($checks['cache'] ?? false) {
            $results['cache'] = $this->checkCache();
            if ($results['cache']['status'] !== 'healthy') {
                $overallStatus = 'degraded';
            }
        }

        $statusCode = $overallStatus === 'healthy' ? 200 : 503;

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'checks' => $results,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ], $statusCode);
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity.
     */
    protected function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'message' => 'Redis connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage accessibility.
     */
    protected function checkStorage(): array
    {
        try {
            $disk = config('filesystems.default');
            $testFile = 'health-check-' . time() . '.txt';
            
            $start = microtime(true);
            Storage::disk($disk)->put($testFile, 'health check');
            $exists = Storage::disk($disk)->exists($testFile);
            Storage::disk($disk)->delete($testFile);
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($exists) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'disk' => $disk,
                    'message' => 'Storage is accessible',
                ];
            }

            return [
                'status' => 'unhealthy',
                'disk' => $disk,
                'message' => 'Storage write/read failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue system.
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $size = Queue::size($connection);

            $status = 'healthy';
            $threshold = config('monitoring.alerting.thresholds.queue_size', 1000);
            
            if ($size > $threshold) {
                $status = 'degraded';
            }

            return [
                'status' => $status,
                'connection' => $connection,
                'size' => $size,
                'message' => $status === 'healthy' 
                    ? 'Queue is operational' 
                    : "Queue size ({$size}) exceeds threshold ({$threshold})",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system.
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            $value = 'test';
            
            $start = microtime(true);
            Cache::put($key, $value, 10);
            $retrieved = Cache::get($key);
            Cache::forget($key);
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $value) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'driver' => config('cache.default'),
                    'message' => 'Cache is operational',
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Cache write/read failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Simple ping endpoint for uptime monitoring.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
