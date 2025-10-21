<?php

namespace Tests\Feature\Auth;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_courses(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($user->can('create', Course::class));
    }

    public function test_hr_admin_can_create_courses(): void
    {
        $user = User::factory()->create(['role' => 'hr_admin']);

        $this->assertTrue($user->can('create', Course::class));
    }

    public function test_instructor_can_create_courses(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertTrue($user->can('create', Course::class));
    }

    public function test_employee_cannot_create_courses(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->assertFalse($user->can('create', Course::class));
    }

    public function test_super_admin_can_update_any_course(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $this->assertTrue($admin->can('update', $course));
    }

    public function test_instructor_can_update_own_course(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $this->assertTrue($instructor->can('update', $course));
    }

    public function test_instructor_cannot_update_other_instructor_course(): void
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor1->id]);

        $this->assertFalse($instructor2->can('update', $course));
    }

    public function test_super_admin_can_delete_any_course(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $this->assertTrue($admin->can('delete', $course));
    }

    public function test_instructor_can_delete_own_course(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $this->assertTrue($instructor->can('delete', $course));
    }

    public function test_instructor_cannot_delete_other_instructor_course(): void
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor1->id]);

        $this->assertFalse($instructor2->can('delete', $course));
    }

    public function test_users_can_view_published_courses(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $this->assertTrue($user->can('view', $course));
    }

    public function test_users_cannot_view_unpublished_courses_they_did_not_create(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create([
            'is_published' => false,
            'created_by' => $instructor->id
        ]);

        $this->assertFalse($user->can('view', $course));
    }

    public function test_instructor_can_view_own_unpublished_course(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create([
            'is_published' => false,
            'created_by' => $instructor->id
        ]);

        $this->assertTrue($instructor->can('view', $course));
    }

    public function test_instructor_can_publish_own_course(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $this->assertTrue($instructor->can('publish', $course));
    }

    public function test_instructor_can_manage_own_course_content(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $this->assertTrue($instructor->can('manageContent', $course));
    }

    public function test_instructor_cannot_manage_other_instructor_course_content(): void
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor1->id]);

        $this->assertFalse($instructor2->can('manageContent', $course));
    }
}
