<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
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

    public function test_can_list_user_enrollments(): void
    {
        Enrollment::factory()->count(3)->create(['user_id' => $this->user->id]);
        Enrollment::factory()->count(2)->create(); // Other user's enrollments

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/enrollments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'course_id', 'user_id', 'status', 'progress_percentage'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_enrollments_by_status(): void
    {
        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/enrollments?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'active');
    }

    public function test_can_enroll_in_course(): void
    {
        $course = Course::factory()->create(['is_published' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/courses/{$course->id}/enroll");

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully enrolled in course',
            ]);

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
    }

    public function test_cannot_enroll_twice_in_same_course(): void
    {
        $course = Course::factory()->create(['is_published' => true]);
        
        Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/courses/{$course->id}/enroll");

        $response->assertStatus(400);
    }


    public function test_can_get_enrollment_details(): void
    {
        $enrollment = Enrollment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/enrollments/{$enrollment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $enrollment->id,
                    'user_id' => $this->user->id,
                ],
            ]);
    }

    public function test_cannot_view_other_users_enrollment(): void
    {
        $otherUser = User::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/enrollments/{$enrollment->id}");

        $response->assertStatus(403);
    }

    public function test_can_unenroll_from_course(): void
    {
        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/enrollments/{$enrollment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully unenrolled from course',
            ]);

        $this->assertDatabaseHas('course_enrollments', [
            'id' => $enrollment->id,
            'status' => 'dropped',
        ]);
    }

    public function test_cannot_unenroll_from_completed_course(): void
    {
        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/enrollments/{$enrollment->id}");

        $response->assertStatus(400);
    }

    public function test_can_paginate_enrollments(): void
    {
        Enrollment::factory()->count(20)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/enrollments?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/enrollments');

        $response->assertStatus(401);
    }
}
