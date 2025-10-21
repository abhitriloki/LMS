<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\OpenAIService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OpenAIServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test API key
        config(['services.openai.api_key' => 'test-api-key']);
        config(['services.openai.cache_enabled' => false]); // Disable cache for tests
    }

    public function test_implements_ai_service_interface()
    {
        $service = new OpenAIService();
        $this->assertInstanceOf(AIServiceInterface::class, $service);
    }

    public function test_generate_text_with_valid_response()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'This is a generated response'
                        ]
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30
                ]
            ], 200)
        ]);

        $service = new OpenAIService();
        $result = $service->generateText('Test prompt');

        $this->assertEquals('This is a generated response', $result);
    }

    public function test_generate_text_with_custom_options()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Custom response'
                        ]
                    ]
                ],
                'usage' => ['total_tokens' => 30]
            ], 200)
        ]);

        $service = new OpenAIService();
        $result = $service->generateText('Test prompt', [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.5,
            'max_tokens' => 1000
        ]);

        $this->assertEquals('Custom response', $result);
        
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return $body['model'] === 'gpt-3.5-turbo' 
                && $body['temperature'] === 0.5
                && $body['max_tokens'] === 1000;
        });
    }

    public function test_generate_text_throws_exception_on_api_failure()
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'API Error'], 500)
        ]);

        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Failed to generate text');

        $service = new OpenAIService();
        $service->generateText('Test prompt');
    }

    public function test_analyze_text_returns_json_array()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'sentiment' => 'positive',
                                'confidence' => 0.95
                            ])
                        ]
                    ]
                ],
                'usage' => ['total_tokens' => 30]
            ], 200)
        ]);

        $service = new OpenAIService();
        $result = $service->analyzeText('This is great!', 'sentiment');

        $this->assertIsArray($result);
        $this->assertEquals('positive', $result['sentiment']);
        $this->assertEquals(0.95, $result['confidence']);
    }

    public function test_generate_image_returns_url()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/image.png'
                    ]
                ]
            ], 200)
        ]);

        $service = new OpenAIService();
        $result = $service->generateImage('A beautiful sunset');

        $this->assertEquals('https://example.com/image.png', $result);
    }

    public function test_generate_image_throws_exception_on_failure()
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'Image generation failed'], 500)
        ]);

        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Failed to generate image');

        $service = new OpenAIService();
        $service->generateImage('Test prompt');
    }

    public function test_transcribe_audio_throws_exception_for_missing_file()
    {
        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Audio file not found');

        $service = new OpenAIService();
        $service->transcribeAudio('/nonexistent/file.mp3');
    }

    public function test_is_available_returns_true_on_success()
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['data' => []], 200)
        ]);

        $service = new OpenAIService();
        $this->assertTrue($service->isAvailable());
    }

    public function test_is_available_returns_false_on_failure()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([], 500)
        ]);

        $service = new OpenAIService();
        $this->assertFalse($service->isAvailable());
    }

    public function test_get_name_returns_openai()
    {
        $service = new OpenAIService();
        $this->assertEquals('OpenAI', $service->getName());
    }

    public function test_rate_limiting_throws_exception_when_exceeded()
    {
        config(['services.openai.rate_limits.text-generation' => 1]);
        
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:text-generation', 0)
            ->andReturn(1);

        $this->expectException(AIServiceException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $service = new OpenAIService();
        $service->generateText('Test prompt');
    }

    public function test_usage_tracking_stores_statistics()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Response']]
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30
                ]
            ], 200)
        ]);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:text-generation', 0)
            ->andReturn(0);
            
        Cache::shouldReceive('put')
            ->with('ai_rate_limit:text-generation', 1, 60)
            ->once();

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/ai_usage:/'), \Mockery::any())
            ->andReturn(['count' => 0, 'tokens' => 0, 'cost' => 0]);
            
        Cache::shouldReceive('put')
            ->with(\Mockery::pattern('/ai_usage:/'), \Mockery::any(), 86400 * 30)
            ->once();

        $service = new OpenAIService();
        $service->generateText('Test prompt');
    }

    public function test_get_usage_stats_returns_array()
    {
        Cache::shouldReceive('get')
            ->andReturn(['count' => 5, 'tokens' => 100, 'cost' => 0.05]);

        $service = new OpenAIService();
        $stats = $service->getUsageStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('text-generation', $stats);
        $this->assertArrayHasKey('text-analysis', $stats);
    }
}
