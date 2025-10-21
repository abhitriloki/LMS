<?php

namespace Tests\Feature\Auth;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_create_assessments(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertTrue($user->can('create', Assessment::class));
    }

    public function test_employee_cannot_create_assessments(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->assertFalse($user->can('create', Assessment::class));
    }

    public function test_instructor_can_update_own_assessment(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $instructor->id
        ]);

        $this->assertTrue($instructor->can('update', $assessment));
    }

    public function test_instructor_cannot_update_other_instructor_assessment(): void
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor1->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $instructor1->id
        ]);

        $this->assertFalse($instructor2->can('update', $assessment));
    }

    public function test_super_admin_can_update_any_assessment(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $instructor->id
        ]);

        $this->assertTrue($admin->can('update', $assessment));
    }

    public function test_enrolled_user_can_take_published_assessment(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true
        ]);

        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active'
        ]);

        $this->assertTrue($user->can('take', $assessment));
    }

    public function test_non_enrolled_user_cannot_take_assessment(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true
        ]);

        $this->assertFalse($user->can('take', $assessment));
    }

    public function test_user_cannot_take_unpublished_assessment(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => false
        ]);

        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active'
        ]);

        $this->assertFalse($user->can('take', $assessment));
    }

    public function test_instructor_can_grade_own_assessment(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $instructor->id
        ]);

        $this->assertTrue($instructor->can('grade', $assessment));
    }

    public function test_instructor_cannot_grade_other_instructor_assessment(): void
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor1->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $instructor1->id
        ]);

        $this->assertFalse($instructor2->can('grade', $assessment));
    }

    public function test_super_admin_can_grade_any_assessment(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $instructor->id
        ]);

        $this->assertTrue($admin->can('grade', $assessment));
    }
}
