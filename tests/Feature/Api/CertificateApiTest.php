<?php

namespace Tests\Feature\Api;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_list_user_certificates(): void
    {
        Certificate::factory()->count(3)->create(['user_id' => $this->user->id]);
        Certificate::factory()->count(2)->create(); // Other user's certificates

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/certificates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'certificate_number', 'issued_at'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_certificate_details(): void
    {
        $certificate = Certificate::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/certificates/{$certificate->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                ],
            ]);
    }

    public function test_cannot_view_other_users_certificate(): void
    {
        $otherUser = User::factory()->create();
        $certificate = Certificate::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/certificates/{$certificate->id}");

        $response->assertStatus(403);
    }

    public function test_can_verify_certificate_without_authentication(): void
    {
        $certificate = Certificate::factory()->create([
            'certificate_number' => 'CERT-2024-001',
        ]);

        $response = $this->postJson('/api/certificates/verify', [
            'certificate_number' => 'CERT-2024-001',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'certificate' => [
                        'certificate_number' => 'CERT-2024-001',
                    ],
                    'is_valid' => true,
                ],
            ]);
    }

    public function test_certificate_verification_returns_404_for_invalid_number(): void
    {
        $response = $this->postJson('/api/certificates/verify', [
            'certificate_number' => 'INVALID-NUMBER',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Certificate not found',
            ]);
    }

    public function test_expired_certificate_is_marked_as_invalid(): void
    {
        $certificate = Certificate::factory()->create([
            'certificate_number' => 'CERT-2024-002',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/certificates/verify', [
            'certificate_number' => 'CERT-2024-002',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_valid', false);
    }

    public function test_can_paginate_certificates(): void
    {
        Certificate::factory()->count(20)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/certificates?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_requires_authentication_for_listing(): void
    {
        $response = $this->getJson('/api/certificates');

        $response->assertStatus(401);
    }
}
