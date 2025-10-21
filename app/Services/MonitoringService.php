<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    /**
     * Record a metric.
     */
    public function recordMetric(string $name, $value, array $tags = []): void
    {
        if (!config('monitoring.metrics.enabled')) {
            return;
        }

        $metric = [
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->timestamp,
        ];

        // Send to DataDog if enabled
        if (config('monitoring.apm.datadog.enabled')) {
            $this->sendToDataDog($metric);
        }

        // Store in cache for dashboard
        $this->storeMetricInCache($metric);
    }

    /**
     * Record request metrics.
     */
    public function recordRequest(string $method, string $uri, int $statusCode, float $duration): void
    {
        if (!config('monitoring.metrics.collect.requests')) {
            return;
        }

        $this->recordMetric('http.request', 1, [
            'method' => $method,
            'uri' => $uri,
            'status' => $statusCode,
        ]);

        $this->recordMetric('http.response_time', $duration, [
            'method' => $method,
            'uri' => $uri,
        ]);

        // Alert on slow requests
        $threshold = config('monitoring.metrics.slow_request_threshold', 2000);
        if ($duration > $threshold) {
            $this->alertSlowRequest($method, $uri, $duration);
        }
    }

    /**
     * Record database query metrics.
     */
    public function recordQuery(string $sql, float $duration): void
    {
        if (!config('monitoring.metrics.collect.database_queries')) {
            return;
        }

        $this->recordMetric('database.query', 1);
        $this->recordMetric('database.query_time', $duration);

        // Alert on slow queries
        $threshold = config('monitoring.metrics.slow_query_threshold', 1000);
        if ($duration > $threshold) {
            $this->alertSlowQuery($sql, $duration);
        }
    }

    /**
     * Record cache metrics.
     */
    public function recordCacheHit(string $key): void
    {
        if (!config('monitoring.metrics.collect.cache_hits')) {
            return;
        }

        $this->recordMetric('cache.hit', 1, ['key' => $key]);
    }

    /**
     * Record cache miss.
     */
    public function recordCacheMiss(string $key): void
    {
        if (!config('monitoring.metrics.collect.cache_hits')) {
            return;
        }

        $this->recordMetric('cache.miss', 1, ['key' => $key]);
    }

    /**
     * Record queue job metrics.
     */
    public function recordQueueJob(string $job, string $status, float $duration = null): void
    {
        if (!config('monitoring.metrics.collect.queue_jobs')) {
            return;
        }

        $this->recordMetric('queue.job', 1, [
            'job' => $job,
            'status' => $status,
        ]);

        if ($duration !== null) {
            $this->recordMetric('queue.job_duration', $duration, [
                'job' => $job,
            ]);
        }
    }

    /**
     * Record error.
     */
    public function recordError(\Throwable $exception, array $context = []): void
    {
        $this->recordMetric('error', 1, [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);

        // Check error rate threshold
        $this->checkErrorRateThreshold();
    }

    /**
     * Send metric to DataDog.
     */
    protected function sendToDataDog(array $metric): void
    {
        try {
            $config = config('monitoring.apm.datadog');
            
            $data = [
                'series' => [
                    [
                        'metric' => $metric['name'],
                        'points' => [[$metric['timestamp'], $metric['value']]],
                        'type' => 'gauge',
                        'tags' => array_merge(
                            $config['tags'],
                            array_map(fn($k, $v) => "$k:$v", array_keys($metric['tags']), $metric['tags'])
                        ),
                    ],
                ],
            ];

            Http::withHeaders([
                'DD-API-KEY' => $config['api_key'],
            ])->post('https://api.datadoghq.com/api/v1/series', $data);
        } catch (\Exception $e) {
            Log::warning('Failed to send metric to DataDog', [
                'error' => $e->getMessage(),
                'metric' => $metric,
            ]);
        }
    }

    /**
     * Store metric in cache for dashboard.
     */
    protected function storeMetricInCache(array $metric): void
    {
        $key = "metrics:{$metric['name']}:" . now()->format('Y-m-d-H');
        
        Cache::remember($key, 3600, function () {
            return [];
        });

        $metrics = Cache::get($key, []);
        $metrics[] = $metric;
        
        Cache::put($key, $metrics, 3600);
    }

    /**
     * Alert on slow request.
     */
    protected function alertSlowRequest(string $method, string $uri, float $duration): void
    {
        Log::warning('Slow request detected', [
            'method' => $method,
            'uri' => $uri,
            'duration_ms' => $duration,
        ]);

        if (config('monitoring.alerting.enabled')) {
            $this->sendAlert('Slow Request', "Request to {$method} {$uri} took {$duration}ms");
        }
    }

    /**
     * Alert on slow query.
     */
    protected function alertSlowQuery(string $sql, float $duration): void
    {
        Log::warning('Slow query detected', [
            'sql' => $sql,
            'duration_ms' => $duration,
        ]);

        if (config('monitoring.alerting.enabled')) {
            $this->sendAlert('Slow Query', "Query took {$duration}ms: " . substr($sql, 0, 100));
        }
    }

    /**
     * Check error rate threshold.
     */
    protected function checkErrorRateThreshold(): void
    {
        $key = 'error_count:' . now()->format('Y-m-d-H-i');
        $count = Cache::increment($key);
        Cache::put($key, $count, 60);

        $threshold = config('monitoring.alerting.thresholds.error_rate', 5);
        
        if ($count >= $threshold) {
            $this->sendAlert(
                'High Error Rate',
                "Error rate threshold exceeded: {$count} errors in the last minute"
            );
        }
    }

    /**
     * Send alert to configured channels.
     */
    protected function sendAlert(string $title, string $message): void
    {
        $channels = config('monitoring.alerting.channels', []);

        // Send to Slack
        if ($channels['slack']['enabled'] ?? false) {
            $this->sendSlackAlert($title, $message, $channels['slack']);
        }

        // Send to Email
        if ($channels['email']['enabled'] ?? false) {
            $this->sendEmailAlert($title, $message, $channels['email']);
        }

        // Send to PagerDuty
        if ($channels['pagerduty']['enabled'] ?? false) {
            $this->sendPagerDutyAlert($title, $message, $channels['pagerduty']);
        }
    }

    /**
     * Send Slack alert.
     */
    protected function sendSlackAlert(string $title, string $message, array $config): void
    {
        try {
            Http::post($config['webhook_url'], [
                'channel' => $config['channel'],
                'username' => 'LMS Monitoring',
                'icon_emoji' => ':warning:',
                'attachments' => [
                    [
                        'color' => 'danger',
                        'title' => $title,
                        'text' => $message,
                        'footer' => config('app.name'),
                        'ts' => now()->timestamp,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Slack alert', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send email alert.
     */
    protected function sendEmailAlert(string $title, string $message, array $config): void
    {
        try {
            foreach ($config['recipients'] as $recipient) {
                \Mail::raw($message, function ($mail) use ($recipient, $title) {
                    $mail->to($recipient)
                        ->subject("[ALERT] {$title}");
                });
            }
        } catch (\Exception $e) {
            Log::error('Failed to send email alert', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send PagerDuty alert.
     */
    protected function sendPagerDutyAlert(string $title, string $message, array $config): void
    {
        try {
            Http::post('https://events.pagerduty.com/v2/enqueue', [
                'routing_key' => $config['integration_key'],
                'event_action' => 'trigger',
                'payload' => [
                    'summary' => $title,
                    'severity' => 'error',
                    'source' => config('app.url'),
                    'custom_details' => [
                        'message' => $message,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send PagerDuty alert', ['error' => $e->getMessage()]);
        }
    }
}
