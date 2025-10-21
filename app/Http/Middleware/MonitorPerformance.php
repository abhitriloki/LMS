<?php

namespace App\Http\Middleware;

use App\Services\MonitoringService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitorPerformance
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected MonitoringService $monitoring
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        $this->monitoring->recordRequest(
            $request->method(),
            $request->path(),
            $response->getStatusCode(),
            $duration
        );

        // Add performance headers in non-production
        if (!app()->isProduction()) {
            $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB');
        }

        return $response;
    }
}
