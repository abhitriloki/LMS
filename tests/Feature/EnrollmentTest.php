<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected EnrollmentService $enrollmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enrollmentService = app(EnrollmentService::class);
    }

    /** @test */
    public function user_can_enroll_in_a_course()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $this->actingAs($user);

        $response = $this->post(route('enrollments.store', $course));

        $response->assertRedirect(route('enrollments.index'));
        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function user_cannot_enroll_in_same_course_twice()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        // First enrollment
        $this->enrollmentService->enroll($user, $course);

        // Attempt second enrollment
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User is already enrolled in this course.');
        
        $this->enrollmentService->enroll($user, $course);
    }

    /** @test */
    public function user_can_view_their_enrolled_courses()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course1 = Course::factory()->create(['is_published' => true]);
        $course2 = Course::factory()->create(['is_published' => true]);

        $this->enrollmentService->enroll($user, $course1);
        $this->enrollmentService->enroll($user, $course2);

        $this->actingAs($user);

        $response = $this->get(route('enrollments.index'));

        $response->assertStatus(200);
        $response->assertSee($course1->title);
        $response->assertSee($course2->title);
    }

    /** @test */
    public function user_can_unenroll_from_non_mandatory_course()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $enrollment = $this->enrollmentService->enroll($user, $course, 'self');

        $this->actingAs($user);

        $response = $this->delete(route('enrollments.destroy', $course));

        $response->assertRedirect(route('enrollments.index'));
        $this->assertDatabaseMissing('course_enrollments', [
            'id' => $enrollment->id,
        ]);
    }

    /** @test */
    public function user_cannot_unenroll_from_mandatory_course()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true, 'is_mandatory' => true]);

        $this->enrollmentService->enroll($user, $course, 'mandatory');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot unenroll from mandatory courses.');

        $this->enrollmentService->unenroll($user, $course);
    }

    /** @test */
    public function enrollment_checks_prerequisites()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $prerequisiteCourse = Course::factory()->create(['is_published' => true]);
        $course = Course::factory()->create([
            'is_published' => true,
            'prerequisites' => [$prerequisiteCourse->id],
        ]);

        // Should not be able to enroll without completing prerequisite
        $canEnroll = $this->enrollmentService->checkPrerequisites($user, $course);
        $this->assertFalse($canEnroll);

        // Complete prerequisite
        $enrollment = $this->enrollmentService->enroll($user, $prerequisiteCourse);
        $enrollment->update(['status' => 'completed']);

        // Should now be able to enroll
        $canEnroll = $this->enrollmentService->checkPrerequisites($user, $course);
        $this->assertTrue($canEnroll);
    }

    /** @test */
    public function user_cannot_enroll_without_completing_prerequisites()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $prerequisiteCourse = Course::factory()->create(['is_published' => true]);
        $course = Course::factory()->create([
            'is_published' => true,
            'prerequisites' => [$prerequisiteCourse->id],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has not completed the required prerequisite courses.');

        $this->enrollmentService->enroll($user, $course);
    }

    /** @test */
    public function service_returns_missing_prerequisites()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $prerequisite1 = Course::factory()->create(['is_published' => true]);
        $prerequisite2 = Course::factory()->create(['is_published' => true]);
        $course = Course::factory()->create([
            'is_published' => true,
            'prerequisites' => [$prerequisite1->id, $prerequisite2->id],
        ]);

        $missingPrerequisites = $this->enrollmentService->getMissingPrerequisites($user, $course);

        $this->assertCount(2, $missingPrerequisites);
        $this->assertTrue($missingPrerequisites->contains($prerequisite1));
        $this->assertTrue($missingPrerequisites->contains($prerequisite2));
    }

    /** @test */
    public function mandatory_courses_auto_enroll_users()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $mandatoryCourse = Course::factory()->create([
            'is_published' => true,
            'is_mandatory' => true,
        ]);

        $enrolledCount = $this->enrollmentService->autoEnrollMandatoryCourses($user);

        $this->assertEquals(1, $enrolledCount);
        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $mandatoryCourse->id,
            'enrollment_type' => 'mandatory',
        ]);
    }

    /** @test */
    public function admin_can_bulk_enroll_users()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);
        $users = User::factory()->count(3)->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $results = $this->enrollmentService->bulkEnroll($users, $course, 'assigned', $admin);

        $this->assertCount(3, $results['success']);
        $this->assertCount(0, $results['failed']);

        foreach ($users as $user) {
            $this->assertDatabaseHas('course_enrollments', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrollment_type' => 'assigned',
                'enrolled_by' => $admin->id,
            ]);
        }
    }

    /** @test */
    public function admin_can_enroll_entire_department()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);
        $department = Department::factory()->create();
        $users = User::factory()->count(5)->create([
            'role' => 'employee',
            'department_id' => $department->id,
        ]);
        $course = Course::factory()->create(['is_published' => true]);

        $results = $this->enrollmentService->enrollDepartment($department, $course, 'assigned', $admin);

        $this->assertCount(5, $results['success']);

        foreach ($users as $user) {
            $this->assertDatabaseHas('course_enrollments', [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);
        }
    }

    /** @test */
    public function enrollment_status_can_be_updated()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = $this->enrollmentService->enroll($user, $course);

        $this->assertEquals('active', $enrollment->status);

        $updatedEnrollment = $this->enrollmentService->updateStatus($enrollment, 'completed');

        $this->assertEquals('completed', $updatedEnrollment->status);
    }

    /** @test */
    public function enrollment_deadline_can_be_updated()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = $this->enrollmentService->enroll($user, $course);

        $newDeadline = now()->addDays(30);
        $updatedEnrollment = $this->enrollmentService->updateDeadline($enrollment, $newDeadline);

        $this->assertEquals($newDeadline->format('Y-m-d'), $updatedEnrollment->deadline->format('Y-m-d'));
    }

    /** @test */
    public function service_returns_upcoming_deadlines()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course1 = Course::factory()->create(['is_published' => true]);
        $course2 = Course::factory()->create(['is_published' => true]);

        // Enrollment with upcoming deadline
        $this->enrollmentService->enroll($user, $course1, 'self', null, now()->addDays(5));

        // Enrollment with far future deadline
        $this->enrollmentService->enroll($user, $course2, 'self', null, now()->addDays(30));

        $upcomingDeadlines = $this->enrollmentService->getUpcomingDeadlines($user, 7);

        $this->assertCount(1, $upcomingDeadlines);
        $this->assertEquals($course1->id, $upcomingDeadlines->first()->course_id);
    }

    /** @test */
    public function service_returns_overdue_enrollments()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $enrollment = $this->enrollmentService->enroll($user, $course, 'self', null, now()->subDays(5));

        $overdueEnrollments = $this->enrollmentService->getOverdueEnrollments($user);

        $this->assertCount(1, $overdueEnrollments);
        $this->assertEquals($course->id, $overdueEnrollments->first()->course_id);
    }

    /** @test */
    public function service_calculates_user_statistics()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course1 = Course::factory()->create(['is_published' => true]);
        $course2 = Course::factory()->create(['is_published' => true]);
        $course3 = Course::factory()->create(['is_published' => true]);

        $enrollment1 = $this->enrollmentService->enroll($user, $course1);
        $enrollment2 = $this->enrollmentService->enroll($user, $course2);
        $enrollment3 = $this->enrollmentService->enroll($user, $course3, 'self', null, now()->subDays(5));

        $enrollment1->update(['status' => 'completed']);

        $statistics = $this->enrollmentService->getUserStatistics($user);

        $this->assertEquals(3, $statistics['total']);
        $this->assertEquals(2, $statistics['active']);
        $this->assertEquals(1, $statistics['completed']);
        $this->assertEquals(1, $statistics['overdue']);
    }

    /** @test */
    public function service_calculates_course_statistics()
    {
        $course = Course::factory()->create(['is_published' => true]);
        $users = User::factory()->count(5)->create(['role' => 'employee']);

        foreach ($users as $index => $user) {
            $enrollment = $this->enrollmentService->enroll($user, $course);
            if ($index < 2) {
                $enrollment->update(['status' => 'completed']);
            }
        }

        $statistics = $this->enrollmentService->getCourseStatistics($course);

        $this->assertEquals(5, $statistics['total']);
        $this->assertEquals(3, $statistics['active']);
        $this->assertEquals(2, $statistics['completed']);
        $this->assertEquals(40, $statistics['completion_rate']); // 2/5 = 40%
    }

    /** @test */
    public function admin_can_view_all_enrollments()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $this->enrollmentService->enroll($user, $course);

        $this->actingAs($admin);

        $response = $this->get(route('admin.enrollments.index'));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($course->title);
    }

    /** @test */
    public function admin_can_view_enrollment_details()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = $this->enrollmentService->enroll($user, $course);

        $this->actingAs($admin);

        $response = $this->get(route('admin.enrollments.show', $enrollment));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($course->title);
    }

    /** @test */
    public function admin_can_update_enrollment()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = $this->enrollmentService->enroll($user, $course);

        $this->actingAs($admin);

        $response = $this->put(route('admin.enrollments.update', $enrollment), [
            'status' => 'suspended',
            'deadline' => now()->addDays(15)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('admin.enrollments.show', $enrollment));
        $this->assertDatabaseHas('course_enrollments', [
            'id' => $enrollment->id,
            'status' => 'suspended',
        ]);
    }

    /** @test */
    public function admin_can_delete_enrollment()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);
        $enrollment = $this->enrollmentService->enroll($user, $course);

        $this->actingAs($admin);

        $response = $this->delete(route('admin.enrollments.destroy', $enrollment));

        $response->assertRedirect(route('admin.enrollments.index'));
        $this->assertDatabaseMissing('course_enrollments', [
            'id' => $enrollment->id,
        ]);
    }

    /** @test */
    public function enrollment_confirmation_page_shows_prerequisites()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $prerequisiteCourse = Course::factory()->create(['is_published' => true]);
        $course = Course::factory()->create([
            'is_published' => true,
            'prerequisites' => [$prerequisiteCourse->id],
        ]);

        $this->actingAs($user);

        $response = $this->get(route('enrollments.show', $course));

        $response->assertStatus(200);
        $response->assertSee($prerequisiteCourse->title);
        $response->assertSee('Complete Prerequisites First');
    }
}
