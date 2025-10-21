<?php

namespace App\Jobs\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GenerateAITextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [30, 60, 120];

    protected string $prompt;
    protected array $options;
    protected string $cacheKey;
    protected ?string $callbackClass;
    protected ?string $callbackMethod;
    protected array $callbackParams;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $prompt,
        array $options = [],
        ?string $cacheKey = null,
        ?string $callbackClass = null,
        ?string $callbackMethod = null,
        array $callbackParams = []
    ) {
        $this->prompt = $prompt;
        $this->options = $options;
        $this->cacheKey = $cacheKey ?? 'ai_text_' . md5($prompt);
        $this->callbackClass = $callbackClass;
        $this->callbackMethod = $callbackMethod;
        $this->callbackParams = $callbackParams;
    }

    /**
     * Execute the job.
     */
    public function handle(AIServiceInterface $aiService): void
    {
        try {
            Log::info('Generating AI text', [
                'prompt_length' => strlen($this->prompt),
                'cache_key' => $this->cacheKey
            ]);

            // Check cache first if enabled
            if (config('services.openai.cache_enabled', true)) {
                $cached = Cache::get($this->cacheKey);
                if ($cached) {
                    Log::info('Using cached AI response', ['cache_key' => $this->cacheKey]);
                    $this->executeCallback($cached);
                    return;
                }
            }

            // Generate text
            $result = $aiService->generateText($this->prompt, $this->options);

            // Cache the result
            if (config('services.openai.cache_enabled', true)) {
                $ttl = config('services.openai.cache_ttl', 3600);
                Cache::put($this->cacheKey, $result, $ttl);
            }

            // Execute callback if provided
            $this->executeCallback($result);

            Log::info('AI text generation completed', [
                'cache_key' => $this->cacheKey,
                'result_length' => strlen($result)
            ]);

        } catch (AIServiceException $e) {
            Log::error('AI text generation failed', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->fail($e);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Execute callback with result
     */
    protected function executeCallback(string $result): void
    {
        if ($this->callbackClass && $this->callbackMethod) {
            try {
                $instance = app($this->callbackClass);
                call_user_func_array(
                    [$instance, $this->callbackMethod],
                    array_merge([$result], $this->callbackParams)
                );
            } catch (\Exception $e) {
                Log::error('Callback execution failed', [
                    'class' => $this->callbackClass,
                    'method' => $this->callbackMethod,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AI text generation job failed permanently', [
            'prompt_length' => strlen($this->prompt),
            'error' => $exception->getMessage()
        ]);
    }
}
