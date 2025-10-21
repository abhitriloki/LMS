<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizationService
{
    /**
     * Enable query logging for debugging
     */
    public function enableQueryLog(): void
    {
        DB::enableQueryLog();
    }

    /**
     * Disable query logging
     */
    public function disableQueryLog(): void
    {
        DB::disableQueryLog();
    }

    /**
     * Get executed queries
     */
    public function getQueryLog(): array
    {
        return DB::getQueryLog();
    }

    /**
     * Analyze queries for N+1 issues
     */
    public function analyzeQueries(): array
    {
        $queries = $this->getQueryLog();
        $analysis = [
            'total_queries' => count($queries),
            'total_time' => 0,
            'slow_queries' => [],
            'duplicate_queries' => [],
            'potential_n_plus_one' => [],
        ];

        $queryPatterns = [];

        foreach ($queries as $query) {
            $analysis['total_time'] += $query['time'];

            // Identify slow queries (> 100ms)
            if ($query['time'] > 100) {
                $analysis['slow_queries'][] = [
                    'query' => $query['query'],
                    'time' => $query['time'],
                    'bindings' => $query['bindings'],
                ];
            }

            // Identify duplicate queries
            $pattern = $this->normalizeQuery($query['query']);
            if (!isset($queryPatterns[$pattern])) {
                $queryPatterns[$pattern] = 0;
            }
            $queryPatterns[$pattern]++;
        }

        // Find queries executed multiple times
        foreach ($queryPatterns as $pattern => $count) {
            if ($count > 5) {
                $analysis['potential_n_plus_one'][] = [
                    'pattern' => $pattern,
                    'count' => $count,
                ];
            } elseif ($count > 1) {
                $analysis['duplicate_queries'][] = [
                    'pattern' => $pattern,
                    'count' => $count,
                ];
            }
        }

        return $analysis;
    }

    /**
     * Normalize query for pattern matching
     */
    protected function normalizeQuery(string $query): string
    {
        // Remove specific values to identify patterns
        $normalized = preg_replace('/\d+/', '?', $query);
        $normalized = preg_replace('/\'[^\']*\'/', '?', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }

    /**
     * Log query analysis
     */
    public function logAnalysis(): void
    {
        $analysis = $this->analyzeQueries();

        Log::info('Query Analysis', [
            'total_queries' => $analysis['total_queries'],
            'total_time' => $analysis['total_time'] . 'ms',
            'slow_queries_count' => count($analysis['slow_queries']),
            'potential_n_plus_one_count' => count($analysis['potential_n_plus_one']),
        ]);

        if (!empty($analysis['slow_queries'])) {
            Log::warning('Slow Queries Detected', $analysis['slow_queries']);
        }

        if (!empty($analysis['potential_n_plus_one'])) {
            Log::warning('Potential N+1 Queries Detected', $analysis['potential_n_plus_one']);
        }
    }

    /**
     * Get query statistics
     */
    public function getQueryStats(): array
    {
        $queries = $this->getQueryLog();
        
        if (empty($queries)) {
            return [
                'count' => 0,
                'total_time' => 0,
                'average_time' => 0,
                'slowest_query' => null,
            ];
        }

        $times = array_column($queries, 'time');
        $slowestIndex = array_search(max($times), $times);

        return [
            'count' => count($queries),
            'total_time' => array_sum($times),
            'average_time' => array_sum($times) / count($times),
            'slowest_query' => [
                'query' => $queries[$slowestIndex]['query'],
                'time' => $queries[$slowestIndex]['time'],
            ],
        ];
    }

    /**
     * Clear query log
     */
    public function clearQueryLog(): void
    {
        DB::flushQueryLog();
    }
}
