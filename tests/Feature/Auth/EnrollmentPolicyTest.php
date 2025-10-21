<?php

namespace Tests\Feature\Auth;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enroll_in_published_course(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $this->assertTrue($user->can('enroll', $course));
    }

    public function test_user_cannot_enroll_in_unpublished_course(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => false]);

        $this->assertFalse($user->can('enroll', $course));
    }

    public function test_user_cannot_enroll_in_course_twice(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active'
        ]);

        $this->assertFalse($user->can('enroll', $course));
    }

    public function test_user_cannot_enroll_without_completing_prerequisites(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $prerequisiteCourse = Course::factory()->create(['is_published' => true]);
        $course = Course::factory()->create([
            'is_published' => true,
            'prerequisites' => [$prerequisiteCourse->id]
        ]);

        $this->assertFalse($user->can('enroll', $course));
    }

    public function test_user_can_enroll_after_completing_prerequisites(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $prerequisiteCourse = Course::factory()->create(['is_published' => true]);
        $course = Course::factory()->create([
            'is_published' => true,
            'prerequisites' => [$prerequisiteCourse->id]
        ]);

        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $prerequisiteCourse->id,
            'status' => 'completed'
        ]);

        $this->assertTrue($user->can('enroll', $course));
    }

    public function test_user_can_view_own_enrollment(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);

        $this->assertTrue($user->can('view', $enrollment));
    }

    public function test_user_cannot_view_other_user_enrollment(): void
    {
        $user1 = User::factory()->create(['role' => 'employee']);
        $user2 = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user1->id,
            'course_id' => $course->id
        ]);

        $this->assertFalse($user2->can('view', $enrollment));
    }

    public function test_super_admin_can_view_any_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);

        $this->assertTrue($admin->can('view', $enrollment));
    }

    public function test_instructor_can_view_enrollment_for_own_course(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create([
            'is_published' => true,
            'created_by' => $instructor->id
        ]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);

        $this->assertTrue($instructor->can('view', $enrollment));
    }

    public function test_user_can_unenroll_from_voluntary_course(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => 'voluntary'
        ]);

        $this->assertTrue($user->can('unenroll', $enrollment));
    }

    public function test_user_cannot_unenroll_from_mandatory_course(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => 'mandatory'
        ]);

        $this->assertFalse($user->can('unenroll', $enrollment));
    }

    public function test_admin_can_unenroll_anyone_from_any_course(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => 'mandatory'
        ]);

        $this->assertTrue($admin->can('unenroll', $enrollment));
    }

    public function test_admin_can_bulk_enroll(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($admin->can('bulkEnroll', Enrollment::class));
    }

    public function test_employee_cannot_bulk_enroll(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->assertFalse($user->can('bulkEnroll', Enrollment::class));
    }

    public function test_admin_can_update_enrollment_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);

        $this->assertTrue($admin->can('updateStatus', $enrollment));
    }

    public function test_employee_cannot_update_enrollment_status(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);

        $this->assertFalse($user->can('updateStatus', $enrollment));
    }
}
