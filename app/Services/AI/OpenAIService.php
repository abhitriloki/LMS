<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenAIService implements AIServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';
    protected int $timeout = 60;
    protected array $rateLimits = [];
    protected array $costTracking = [];

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        
        if (empty($this->apiKey)) {
            throw new AIServiceException('OpenAI API key not configured');
        }
    }

    /**
     * Generate text using GPT-4
     */
    public function generateText(string $prompt, array $options = []): string
    {
        // Check if caching is enabled and cache exists
        $cacheable = $options['cache'] ?? false;
        $cacheKey = null;
        
        if ($cacheable) {
            $cacheKey = 'ai.text.' . md5($prompt . json_encode($options));
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                Log::info('AI response served from cache', ['cache_key' => $cacheKey]);
                return $cached;
            }
        }

        $this->checkRateLimit('text-generation');

        $model = $options['model'] ?? 'gpt-4';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            if ($response->failed()) {
                throw new AIServiceException(
                    'OpenAI API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();
            
            // Track usage and cost
            $this->trackUsage('text-generation', $data['usage'] ?? []);
            
            $this->incrementRateLimit('text-generation');

            $result = $data['choices'][0]['message']['content'] ?? '';
            
            // Cache the result if caching is enabled
            if ($cacheable && $cacheKey) {
                $cacheTtl = $options['cache_ttl'] ?? 3600; // Default 1 hour
                Cache::put($cacheKey, $result, $cacheTtl);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('OpenAI text generation failed', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt)
            ]);
            
            throw new AIServiceException('Failed to generate text: ' . $e->getMessage());
        }
    }

    /**
     * Analyze text for specific task
     */
    public function analyzeText(string $text, string $task): array
    {
        // Cache analysis results for 1 hour
        $cacheKey = 'ai.analysis.' . md5($text . $task);
        
        return Cache::remember($cacheKey, 3600, function () use ($text, $task) {
            $this->checkRateLimit('text-analysis');

            $prompt = $this->buildAnalysisPrompt($text, $task);

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => 'gpt-4',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert text analyzer. Return your analysis as valid JSON.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.3,
                    'response_format' => ['type' => 'json_object'],
                ]);

                if ($response->failed()) {
                    throw new AIServiceException('Text analysis failed: ' . $response->body());
                }

                $data = $response->json();
                $this->trackUsage('text-analysis', $data['usage'] ?? []);
                $this->incrementRateLimit('text-analysis');

                $content = $data['choices'][0]['message']['content'] ?? '{}';
                return json_decode($content, true) ?? [];

            } catch (\Exception $e) {
                Log::error('OpenAI text analysis failed', [
                    'error' => $e->getMessage(),
                    'task' => $task
                ]);
                
                throw new AIServiceException('Failed to analyze text: ' . $e->getMessage());
            }
        });
    }

    /**
     * Generate image using DALL-E 3
     */
    public function generateImage(string $prompt): string
    {
        $this->checkRateLimit('image-generation');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . '/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard',
            ]);

            if ($response->failed()) {
                throw new AIServiceException('Image generation failed: ' . $response->body());
            }

            $data = $response->json();
            $this->trackUsage('image-generation', ['images' => 1]);
            $this->incrementRateLimit('image-generation');

            return $data['data'][0]['url'] ?? '';

        } catch (\Exception $e) {
            Log::error('OpenAI image generation failed', [
                'error' => $e->getMessage(),
                'prompt' => $prompt
            ]);
            
            throw new AIServiceException('Failed to generate image: ' . $e->getMessage());
        }
    }

    /**
     * Transcribe audio using Whisper
     */
    public function transcribeAudio(string $audioPath): string
    {
        $this->checkRateLimit('audio-transcription');

        if (!file_exists($audioPath)) {
            throw new AIServiceException('Audio file not found: ' . $audioPath);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout($this->timeout)
            ->attach('file', file_get_contents($audioPath), basename($audioPath))
            ->post($this->baseUrl . '/audio/transcriptions', [
                'model' => 'whisper-1',
            ]);

            if ($response->failed()) {
                throw new AIServiceException('Audio transcription failed: ' . $response->body());
            }

            $data = $response->json();
            $this->trackUsage('audio-transcription', ['duration' => filesize($audioPath)]);
            $this->incrementRateLimit('audio-transcription');

            return $data['text'] ?? '';

        } catch (\Exception $e) {
            Log::error('OpenAI audio transcription failed', [
                'error' => $e->getMessage(),
                'file' => $audioPath
            ]);
            
            throw new AIServiceException('Failed to transcribe audio: ' . $e->getMessage());
        }
    }

    /**
     * Convert text to speech
     */
    public function textToSpeech(string $text): string
    {
        $this->checkRateLimit('text-to-speech');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . '/audio/speech', [
                'model' => 'tts-1',
                'input' => $text,
                'voice' => 'alloy',
            ]);

            if ($response->failed()) {
                throw new AIServiceException('Text-to-speech failed: ' . $response->body());
            }

            // Save audio file
            $filename = 'tts_' . time() . '_' . uniqid() . '.mp3';
            $path = storage_path('app/public/audio/' . $filename);
            
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            
            file_put_contents($path, $response->body());

            $this->trackUsage('text-to-speech', ['characters' => strlen($text)]);
            $this->incrementRateLimit('text-to-speech');

            return 'audio/' . $filename;

        } catch (\Exception $e) {
            Log::error('OpenAI text-to-speech failed', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text)
            ]);
            
            throw new AIServiceException('Failed to convert text to speech: ' . $e->getMessage());
        }
    }

    /**
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(5)
            ->get($this->baseUrl . '/models');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get service name
     */
    public function getName(): string
    {
        return 'OpenAI';
    }

    /**
     * Check rate limit
     */
    protected function checkRateLimit(string $operation): void
    {
        $limit = config("services.openai.rate_limits.{$operation}", 60);
        $window = 60; // 1 minute window
        
        $key = "ai_rate_limit:{$operation}";
        $current = Cache::get($key, 0);

        if ($current >= $limit) {
            throw new AIServiceException(
                "Rate limit exceeded for {$operation}. Limit: {$limit} per minute."
            );
        }
    }

    /**
     * Increment rate limit counter
     */
    protected function incrementRateLimit(string $operation): void
    {
        $key = "ai_rate_limit:{$operation}";
        $window = 60; // 1 minute window
        
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, $window);
    }

    /**
     * Track usage and cost
     */
    protected function trackUsage(string $operation, array $usage): void
    {
        $date = now()->format('Y-m-d');
        $key = "ai_usage:{$date}:{$operation}";
        
        $currentUsage = Cache::get($key, [
            'count' => 0,
            'tokens' => 0,
            'cost' => 0,
        ]);

        $currentUsage['count']++;
        
        if (isset($usage['total_tokens'])) {
            $currentUsage['tokens'] += $usage['total_tokens'];
            $currentUsage['cost'] += $this->calculateCost($operation, $usage);
        }

        Cache::put($key, $currentUsage, 86400 * 30); // Keep for 30 days

        Log::info('AI usage tracked', [
            'operation' => $operation,
            'usage' => $usage,
            'daily_total' => $currentUsage
        ]);
    }

    /**
     * Calculate cost based on usage
     */
    protected function calculateCost(string $operation, array $usage): float
    {
        $costs = config('services.openai.costs', [
            'gpt-4' => ['input' => 0.03, 'output' => 0.06], // per 1K tokens
            'gpt-3.5-turbo' => ['input' => 0.0015, 'output' => 0.002],
            'dall-e-3' => 0.040, // per image
            'whisper-1' => 0.006, // per minute
            'tts-1' => 0.015, // per 1M characters
        ]);

        $cost = 0;

        if (isset($usage['prompt_tokens']) && isset($usage['completion_tokens'])) {
            $model = 'gpt-4';
            $cost += ($usage['prompt_tokens'] / 1000) * $costs[$model]['input'];
            $cost += ($usage['completion_tokens'] / 1000) * $costs[$model]['output'];
        }

        return $cost;
    }

    /**
     * Build analysis prompt
     */
    protected function buildAnalysisPrompt(string $text, string $task): string
    {
        $prompts = [
            'readability' => "Analyze the readability of the following text. Return JSON with: reading_level, complexity_score, suggestions.",
            'sentiment' => "Analyze the sentiment of the following text. Return JSON with: sentiment, confidence, key_phrases.",
            'summary' => "Summarize the following text. Return JSON with: summary, key_points, word_count.",
            'keywords' => "Extract keywords from the following text. Return JSON with: keywords array, topics array.",
        ];

        $taskPrompt = $prompts[$task] ?? "Analyze the following text for: {$task}. Return results as JSON.";

        return "{$taskPrompt}\n\nText:\n{$text}";
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');
        $operations = ['text-generation', 'text-analysis', 'image-generation', 'audio-transcription', 'text-to-speech'];
        
        $stats = [];
        foreach ($operations as $operation) {
            $key = "ai_usage:{$date}:{$operation}";
            $stats[$operation] = Cache::get($key, [
                'count' => 0,
                'tokens' => 0,
                'cost' => 0,
            ]);
        }

        return $stats;
    }
}
