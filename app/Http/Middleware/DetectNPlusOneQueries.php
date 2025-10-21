<?php

namespace App\Http\Middleware;

use App\Services\QueryOptimizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DetectNPlusOneQueries
{
    protected QueryOptimizationService $queryService;

    public function __construct(QueryOptimizationService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enable in local environment
        if (!app()->environment('local')) {
            return $next($request);
        }

        // Enable query logging
        $this->queryService->enableQueryLog();

        $response = $next($request);

        // Analyze queries
        $analysis = $this->queryService->analyzeQueries();

        // Log warnings if issues detected
        if ($analysis['total_queries'] > 50) {
            Log::warning('High query count detected', [
                'url' => $request->fullUrl(),
                'count' => $analysis['total_queries'],
                'time' => $analysis['total_time'] . 'ms',
            ]);
        }

        if (!empty($analysis['potential_n_plus_one'])) {
            Log::warning('Potential N+1 queries detected', [
                'url' => $request->fullUrl(),
                'issues' => $analysis['potential_n_plus_one'],
            ]);
        }

        // Clear query log
        $this->queryService->clearQueryLog();

        return $response;
    }
}
