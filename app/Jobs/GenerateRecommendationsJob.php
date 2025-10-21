<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AI\AIRecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public int $limit;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, int $limit = 5)
    {
        $this->user = $user;
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(AIRecommendationService $recommendationService): void
    {
        try {
            Log::info('Generating recommendations for user', [
                'user_id' => $this->user->id,
                'limit' => $this->limit
            ]);

            $recommendations = $recommendationService->generateRecommendations($this->user, $this->limit);

            Log::info('Recommendations generated successfully', [
                'user_id' => $this->user->id,
                'count' => count($recommendations)
            ]);

            // Optionally send notification to user
            // $this->user->notify(new RecommendationsGeneratedNotification($recommendations));

        } catch (\Exception $e) {
            Log::error('Failed to generate recommendations in job', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateRecommendationsJob failed permanently', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage()
        ]);

        // Optionally notify user of failure
        // $this->user->notify(new RecommendationGenerationFailedNotification());
    }
}
