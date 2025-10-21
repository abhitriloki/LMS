<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseApiTest extends TestCase
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

    public function test_can_list_published_courses(): void
    {
        Course::factory()->count(3)->create(['is_published' => true]);
        Course::factory()->count(2)->create(['is_published' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/courses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'slug', 'description', 'difficulty_level'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_courses_by_difficulty(): void
    {
        Course::factory()->create(['difficulty_level' => 'beginner', 'is_published' => true]);
        Course::factory()->create(['difficulty_level' => 'advanced', 'is_published' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/courses?difficulty_level=beginner');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.difficulty_level', 'beginner');
    }

    public function test_can_search_courses(): void
    {
        Course::factory()->create([
            'title' => 'JavaScript Basics',
            'is_published' => true,
        ]);
        Course::factory()->create([
            'title' => 'Python Advanced',
            'is_published' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/courses?search=JavaScript');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'JavaScript Basics');
    }

    public function test_can_sort_courses(): void
    {
        Course::factory()->create(['title' => 'B Course', 'is_published' => true]);
        Course::factory()->create(['title' => 'A Course', 'is_published' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/courses?sort_by=title&sort_order=asc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'A Course')
            ->assertJsonPath('data.1.title', 'B Course');
    }

    public function test_can_paginate_courses(): void
    {
        Course::factory()->count(20)->create(['is_published' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/courses?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }


    public function test_can_get_course_details(): void
    {
        $category = CourseCategory::factory()->create();
        $course = Course::factory()->create([
            'is_published' => true,
            'category_id' => $category->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $course->id,
                    'title' => $course->title,
                ],
            ]);
    }

    public function test_cannot_get_unpublished_course(): void
    {
        $course = Course::factory()->create(['is_published' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/courses/{$course->id}");

        $response->assertStatus(404);
    }

    public function test_can_get_course_modules(): void
    {
        $course = Course::factory()
            ->hasModules(2)
            ->create(['is_published' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/courses/{$course->id}/modules");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'order_index'],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_category(): void
    {
        $category = CourseCategory::factory()->create();
        Course::factory()->create([
            'category_id' => $category->id,
            'is_published' => true,
        ]);
        Course::factory()->create(['is_published' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/courses?category_id={$category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_tags(): void
    {
        Course::factory()->create([
            'tags' => ['javascript', 'frontend'],
            'is_published' => true,
        ]);
        Course::factory()->create([
            'tags' => ['python', 'backend'],
            'is_published' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/courses?tags=javascript');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/courses');

        $response->assertStatus(401);
    }
}
