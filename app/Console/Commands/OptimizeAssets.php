<?php

namespace App\Console\Commands;

use App\Services\AssetOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:optimize 
                            {--path= : Specific path to optimize}
                            {--cleanup : Clean up old cached assets}
                            {--stats : Show asset statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize images and assets for better performance';

    protected AssetOptimizationService $assetService;

    public function __construct(AssetOptimizationService $assetService)
    {
        parent::__construct();
        $this->assetService = $assetService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('stats')) {
            return $this->showStats();
        }

        if ($this->option('cleanup')) {
            return $this->cleanup();
        }

        return $this->optimizeImages();
    }

    /**
     * Optimize images
     */
    protected function optimizeImages(): int
    {
        $path = $this->option('path') ?: storage_path('app/public');

        if (!File::exists($path)) {
            $this->error("Path does not exist: {$path}");
            return Command::FAILURE;
        }

        $this->info("Optimizing images in: {$path}");
        $this->newLine();

        $images = File::allFiles($path);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $optimized = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar(count($images));
        $bar->start();

        foreach ($images as $image) {
            if (in_array(strtolower($image->getExtension()), $imageExtensions)) {
                if ($this->assetService->optimizeImage($image->getPathname())) {
                    $optimized++;
                } else {
                    $failed++;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Optimization complete!");
        $this->info("Optimized: {$optimized} images");
        
        if ($failed > 0) {
            $this->warn("Failed: {$failed} images");
        }

        return Command::SUCCESS;
    }

    /**
     * Clean up old assets
     */
    protected function cleanup(): int
    {
        $this->info('Cleaning up old cached assets...');

        $count = $this->assetService->cleanupOldAssets(30);

        $this->info("Cleaned up {$count} old assets.");

        return Command::SUCCESS;
    }

    /**
     * Show asset statistics
     */
    protected function showStats(): int
    {
        $this->info('Asset Statistics:');
        $this->newLine();

        $stats = $this->assetService->getAssetStats();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Public Directory Size', $this->assetService->formatBytes($stats['public_size'])],
                ['Storage Directory Size', $this->assetService->formatBytes($stats['storage_size'])],
                ['Total Files', number_format($stats['total_files'])],
            ]
        );

        return Command::SUCCESS;
    }
}
