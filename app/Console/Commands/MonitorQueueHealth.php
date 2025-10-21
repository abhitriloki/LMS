<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class MonitorQueueHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health {--detailed : Show detailed queue information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor queue health and display statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Queue Health Monitor');
        $this->newLine();

        // Check Redis connection
        if (!$this->checkRedisConnection()) {
            $this->error('Redis connection failed!');
            return Command::FAILURE;
        }

        $this->info('✓ Redis connection: OK');
        $this->newLine();

        // Get queue statistics
        $stats = $this->getQueueStats();

        // Display statistics
        $this->displayStats($stats);

        // Show detailed information if requested
        if ($this->option('detailed')) {
            $this->newLine();
            $this->displayDetailedInfo();
        }

        // Check for issues
        $this->checkForIssues($stats);

        return Command::SUCCESS;
    }

    /**
     * Check Redis connection
     */
    protected function checkRedisConnection(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get queue statistics
     */
    protected function getQueueStats(): array
    {
        $queues = ['high', 'default', 'low'];
        $stats = [];

        foreach ($queues as $queue) {
            $stats[$queue] = [
                'pending' => $this->getQueueSize($queue),
                'processing' => $this->getProcessingCount($queue),
            ];
        }

        // Get failed jobs count
        $stats['failed'] = DB::table('failed_jobs')->count();

        return $stats;
    }

    /**
     * Get queue size
     */
    protected function getQueueSize(string $queue): int
    {
        try {
            $prefix = config('database.redis.options.prefix', '');
            $key = $prefix . 'queues:' . $queue;
            return Redis::connection()->llen($key);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get processing count
     */
    protected function getProcessingCount(string $queue): int
    {
        try {
            $prefix = config('database.redis.options.prefix', '');
            $key = $prefix . 'queues:' . $queue . ':reserved';
            return Redis::connection()->zcard($key);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Display statistics
     */
    protected function displayStats(array $stats): void
    {
        $this->info('Queue Statistics:');
        
        $tableData = [];
        foreach (['high', 'default', 'low'] as $queue) {
            $tableData[] = [
                'Queue' => ucfirst($queue),
                'Pending' => $stats[$queue]['pending'],
                'Processing' => $stats[$queue]['processing'],
                'Total' => $stats[$queue]['pending'] + $stats[$queue]['processing'],
            ];
        }

        $this->table(
            ['Queue', 'Pending', 'Processing', 'Total'],
            $tableData
        );

        $this->newLine();
        $this->info("Failed Jobs: {$stats['failed']}");
    }

    /**
     * Display detailed information
     */
    protected function displayDetailedInfo(): void
    {
        $this->info('Detailed Queue Information:');
        $this->newLine();

        // Get recent failed jobs
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get();

        if ($failedJobs->isNotEmpty()) {
            $this->warn('Recent Failed Jobs:');
            foreach ($failedJobs as $job) {
                $this->line("- {$job->queue} | {$job->failed_at}");
                $this->line("  Exception: " . substr($job->exception, 0, 100) . '...');
            }
        } else {
            $this->info('No recent failed jobs.');
        }
    }

    /**
     * Check for issues
     */
    protected function checkForIssues(array $stats): void
    {
        $this->newLine();
        $issues = [];

        // Check for high pending count
        foreach (['high', 'default', 'low'] as $queue) {
            if ($stats[$queue]['pending'] > 100) {
                $issues[] = "High pending count in '{$queue}' queue: {$stats[$queue]['pending']}";
            }
        }

        // Check for failed jobs
        if ($stats['failed'] > 10) {
            $issues[] = "High number of failed jobs: {$stats['failed']}";
        }

        if (empty($issues)) {
            $this->info('✓ No issues detected');
        } else {
            $this->warn('⚠ Issues detected:');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
        }
    }
}
