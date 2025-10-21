<?php

namespace App\Console\Commands;

use App\Services\CacheInvalidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ManageLMSCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:cache {action : The action to perform (clear, warm, stats)}
                            {--type= : Specific cache type to manage (catalog, progress, ai)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage LMS caching system';

    protected CacheInvalidationService $cacheService;

    public function __construct(CacheInvalidationService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $type = $this->option('type');

        return match ($action) {
            'clear' => $this->clearCache($type),
            'warm' => $this->warmCache($type),
            'stats' => $this->showStats(),
            default => $this->error("Unknown action: {$action}"),
        };
    }

    /**
     * Clear cache
     */
    protected function clearCache(?string $type): int
    {
        if (!$type) {
            if ($this->confirm('This will clear ALL caches. Are you sure?')) {
                $this->cacheService->invalidateAll();
                $this->info('All caches cleared successfully.');
                return Command::SUCCESS;
            }
            return Command::FAILURE;
        }

        match ($type) {
            'catalog' => $this->cacheService->invalidateCatalog(),
            'ai' => $this->cacheService->invalidateAICache(),
            default => $this->error("Unknown cache type: {$type}"),
        };

        $this->info("Cache type '{$type}' cleared successfully.");
        return Command::SUCCESS;
    }

    /**
     * Warm up cache
     */
    protected function warmCache(?string $type): int
    {
        if (!$type || $type === 'catalog') {
            $this->info('Warming up catalog cache...');
            $this->cacheService->warmCatalogCache();
            $this->info('Catalog cache warmed up successfully.');
        }

        if (!$type) {
            $this->info('All caches warmed up successfully.');
        }

        return Command::SUCCESS;
    }

    /**
     * Show cache statistics
     */
    protected function showStats(): int
    {
        $stats = $this->cacheService->getCacheStats();

        $this->info('Cache Statistics:');
        $this->table(
            ['Key', 'Value'],
            collect($stats)->map(fn($value, $key) => [$key, $value])->values()
        );

        return Command::SUCCESS;
    }
}
