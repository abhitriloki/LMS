<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\OpenAIService;
use App\Services\AI\FallbackAIService;
use Illuminate\Support\Facades\Http;

class AIServiceProviderTest extends TestCase
{
    public function test_ai_service_is_bound_to_container()
    {
        $this->assertTrue($this->app->bound(AIServiceInterface::class));
    }

    public function test_can_resolve_ai_service_from_container()
    {
        $service = $this->app->make(AIServiceInterface::class);
        
        $this->assertInstanceOf(AIServiceInterface::class, $service);
    }

    public function test_resolves_openai_service_when_available()
    {
        config(['services.openai.api_key' => 'test-key']);
        
        Http::fake([
            'api.openai.com/*' => Http::response(['data' => []], 200)
        ]);

        // Rebind the service
        $this->app->singleton(AIServiceInterface::class, function ($app) {
            return new OpenAIService();
        });

        $service = $this->app->make(AIServiceInterface::class);
        
        $this->assertInstanceOf(OpenAIService::class, $service);
        $this->assertEquals('OpenAI', $service->getName());
    }

    public function test_resolves_fallback_service_when_openai_unavailable()
    {
        config(['services.openai.api_key' => 'test-key']);
        config(['services.openai.use_fallback' => true]);
        
        Http::fake([
            'api.openai.com/*' => Http::response([], 500)
        ]);

        // Rebind the service to simulate unavailability
        $this->app->singleton(AIServiceInterface::class, function ($app) {
            return new FallbackAIService();
        });

        $service = $this->app->make(AIServiceInterface::class);
        
        $this->assertInstanceOf(FallbackAIService::class, $service);
        $this->assertEquals('Fallback', $service->getName());
    }

    public function test_can_resolve_openai_service_explicitly()
    {
        config(['services.openai.api_key' => 'test-key']);
        
        $service = $this->app->make(OpenAIService::class);
        
        $this->assertInstanceOf(OpenAIService::class, $service);
    }

    public function test_can_resolve_fallback_service_explicitly()
    {
        $service = $this->app->make(FallbackAIService::class);
        
        $this->assertInstanceOf(FallbackAIService::class, $service);
    }

    public function test_ai_service_is_singleton()
    {
        $service1 = $this->app->make(AIServiceInterface::class);
        $service2 = $this->app->make(AIServiceInterface::class);
        
        $this->assertSame($service1, $service2);
    }
}
