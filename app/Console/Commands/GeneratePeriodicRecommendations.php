<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Jobs\GenerateRecommendationsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GeneratePeriodicRecommendations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'recommendations:generate
                            {--user-id= : Generate recommendations for a specific user}
                            {--limit=5 : Number of recommendations to generate per user}
                            {--force : Force regeneration even if user has active recommendations}';

    /**
     * The console command description.
     */
    protected $description = 'Generate periodic AI-powered course recommendations for users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->option('user-id');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        if ($userId) {
            // Generate for specific user
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return self::FAILURE;
            }

            $this->generateForUser($user, $limit, $force);
            return self::SUCCESS;
        }

        // Generate for all active users who need recommendations
        $this->info('Generating recommendations for users who need them...');
        
        $users = $this->getUsersNeedingRecommendations($force);
        $count = $users->count();

        if ($count === 0) {
            $this->info('No users need recommendations at this time.');
            return self::SUCCESS;
        }

        $this->info("Found {$count} users needing recommendations.");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($users as $user) {
            $this->generateForUser($user, $limit, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Queued recommendation generation for {$count} users.");

        return self::SUCCESS;
    }

    /**
     * Generate recommendations for a specific user
     */
    protected function generateForUser(User $user, int $limit, bool $force): void
    {
        try {
            // Check if user needs recommendations
            if (!$force) {
                $activeCount = $user->recommendations()
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->count();

                if ($activeCount >= 3) {
                    $this->line("User {$user->id} already has sufficient recommendations.");
                    return;
                }
            }

            // Dispatch job to queue
            GenerateRecommendationsJob::dispatch($user, $limit);

            Log::info('Queued recommendation generation', [
                'user_id' => $user->id,
                'limit' => $limit
            ]);

        } catch (\Exception $e) {
            $this->error("Failed to queue recommendations for user {$user->id}: {$e->getMessage()}");
            
            Log::error('Failed to queue recommendation generation', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get users who need recommendations
     */
    protected function getUsersNeedingRecommendations(bool $force): \Illuminate\Database\Eloquent\Collection
    {
        if ($force) {
            // Get all active users
            return User::where('role', '!=', 'super_admin')
                ->whereNotNull('email_verified_at')
                ->get();
        }

        // Get users with fewer than 3 active recommendations
        return User::where('role', '!=', 'super_admin')
            ->whereNotNull('email_verified_at')
            ->whereDoesntHave('recommendations', function ($query) {
                $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->havingRaw('COUNT(*) >= 3');
            })
            ->orWhereHas('recommendations', function ($query) {
                $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
            }, '<', 3)
            ->get();
    }
}
