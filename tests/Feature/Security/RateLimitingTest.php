<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rate_limiting_blocks_after_max_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => $this->stringContains('Too many login attempts'),
        ]);
    }

    public function test_successful_login_clears_rate_limit(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Make 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Successful login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/dashboard');

        // Should be able to login again immediately
        $this->post('/logout');
        
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_registration_rate_limiting(): void
    {
        // Make 5 registration attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [
                'name' => "Test User $i",
                'email' => "test$i@example.com",
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/register', [
            'name' => 'Test User 6',
            'email' => 'test6@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(429);
    }

    public function test_password_reset_rate_limiting(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Make 5 password reset requests
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', [
                'email' => 'test@example.com',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
    }
}
