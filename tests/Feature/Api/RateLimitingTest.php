<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiter before each test
        RateLimiter::clear('api');
        RateLimiter::clear('auth');
    }

    public function test_authentication_endpoints_are_rate_limited(): void
    {
        // Make 6 requests (rate limit is 5 per minute for auth endpoints)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);

            if ($i < 5) {
                $this->assertNotEquals(429, $response->status());
            }
        }

        // 6th request should be rate limited
        $response->assertStatus(429);
    }

    public function test_register_endpoint_is_rate_limited(): void
    {
        // Make 6 requests
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/register', [
                'name' => 'Test User',
                'email' => "test{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($i < 5) {
                $this->assertNotEquals(429, $response->status());
            }
        }

        // 6th request should be rate limited
        $response->assertStatus(429);
    }

    public function test_general_api_endpoints_have_higher_rate_limit(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Make 61 requests (rate limit is 60 per minute for general API)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/me');

            if ($i < 60) {
                $this->assertNotEquals(429, $response->status());
            }
        }

        // 61st request should be rate limited
        $response->assertStatus(429);
    }

    public function test_rate_limit_headers_are_present(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    public function test_rate_limit_is_per_user_for_authenticated_requests(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $token1 = $user1->createToken('test-token')->plainTextToken;
        $token2 = $user2->createToken('test-token')->plainTextToken;

        // Make 60 requests with user1
        for ($i = 0; $i < 60; $i++) {
            $this->withHeader('Authorization', 'Bearer ' . $token1)
                ->getJson('/api/me');
        }

        // User2 should still be able to make requests
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/me');

        $response->assertStatus(200);
    }

    public function test_rate_limit_is_per_ip_for_unauthenticated_requests(): void
    {
        // This test verifies that unauthenticated requests are rate limited by IP
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
        }

        $response->assertStatus(429);
    }
}
