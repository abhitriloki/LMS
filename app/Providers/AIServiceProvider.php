<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\OpenAIService;
use App\Services\AI\FallbackAIService;
use Illuminate\Support\Facades\Log;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the AI service with fallback support
        $this->app->singleton(AIServiceInterface::class, function ($app) {
            try {
                $service = new OpenAIService();
                
                // Check if OpenAI service is available
                if ($service->isAvailable()) {
                    Log::info('OpenAI service registered successfully');
                    return $service;
                }
                
                // Fall back to fallback service if OpenAI is not available
                if (config('services.openai.use_fallback', true)) {
                    Log::warning('OpenAI service unavailable, using fallback service');
                    return new FallbackAIService();
                }
                
                throw new \Exception('AI service is not available and fallback is disabled');
                
            } catch (\Exception $e) {
                Log::error('Failed to initialize AI service', [
                    'error' => $e->getMessage()
                ]);
                
                // Use fallback service if enabled
                if (config('services.openai.use_fallback', true)) {
                    return new FallbackAIService();
                }
                
                throw $e;
            }
        });

        // Register OpenAI service explicitly
        $this->app->singleton(OpenAIService::class, function ($app) {
            return new OpenAIService();
        });

        // Register Fallback service explicitly
        $this->app->singleton(FallbackAIService::class, function ($app) {
            return new FallbackAIService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Log AI service initialization
        if ($this->app->bound(AIServiceInterface::class)) {
            $service = $this->app->make(AIServiceInterface::class);
            Log::info('AI Service Provider booted', [
                'service' => $service->getName(),
                'available' => $service->isAvailable()
            ]);
        }
    }
}
