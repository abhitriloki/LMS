<?php

namespace Tests\Feature;

use App\Events\CourseCompleted;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\ProgressTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProgressTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Course $course;
    protected Enrollment $enrollment;
    protected ProgressTrackingService $progressService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->course = Course::factory()->create(['is_published' => true]);
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->progressService = app(ProgressTrackingService::class);
    }

    /** @test */
    public function it_calculates_enrollment_progress_correctly()
    {
        // Create 4 lessons
        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lessons = CourseLesson::factory()->count(4)->create(['module_id' => $module->id]);

        // Complete 2 lessons (50%)
        foreach ($lessons->take(2) as $lesson) {
            LessonProgress::create([
                'enrollment_id' => $this->enrollment->id,
                'lesson_id' => $lesson->id,
                'status' => 'completed',
                'progress_percentage' => 100,
                'time_spent' => 300,
                'first_accessed_at' => now(),
                'last_accessed_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $result = $this->progressService->updateEnrollmentProgress($this->enrollment);

        $this->assertEquals(50, $result['progress_percentage']);
        $this->assertEquals(2, $result['completed_lessons']);
        $this->assertEquals(4, $result['total_lessons']);

        $this->assertEquals(50, $this->enrollment->fresh()->progress_percentage);
    }

    /** @test */
    public function it_returns_zero_progress_for_course_with_no_lessons()
    {
        $result = $this->progressService->updateEnrollmentProgress($this->enrollment);

        $this->assertEquals(0, $result['progress_percentage']);
        $this->assertEquals(0, $result['completed_lessons']);
        $this->assertEquals(0, $result['total_lessons']);
    }

    /** @test */
    public function it_marks_enrollment_complete_when_all_lessons_finished()
    {
        Event::fake();

        // Create 2 lessons
        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lessons = CourseLesson::factory()->count(2)->create(['module_id' => $module->id]);

        // Complete all lessons
        foreach ($lessons as $lesson) {
            LessonProgress::create([
                'enrollment_id' => $this->enrollment->id,
                'lesson_id' => $lesson->id,
                'status' => 'completed',
                'progress_percentage' => 100,
                'time_spent' => 300,
                'first_accessed_at' => now(),
                'last_accessed_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $this->progressService->updateEnrollmentProgress($this->enrollment);

        $this->assertEquals('completed', $this->enrollment->fresh()->status);
        $this->assertNotNull($this->enrollment->fresh()->completion_date);

        Event::assertDispatched(CourseCompleted::class, function ($event) {
            return $event->enrollment->id === $this->enrollment->id;
        });
    }

    /** @test */
    public function it_does_not_mark_enrollment_complete_if_already_completed()
    {
        Event::fake();

        $this->enrollment->update([
            'status' => 'completed',
            'completion_date' => now()->subDay(),
        ]);

        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lesson = CourseLesson::factory()->create(['module_id' => $module->id]);

        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'time_spent' => 300,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        $this->progressService->updateEnrollmentProgress($this->enrollment);

        // Should not dispatch event again
        Event::assertNotDispatched(CourseCompleted::class);
    }

    /** @test */
    public function it_calculates_course_completion_percentage()
    {
        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lessons = CourseLesson::factory()->count(5)->create(['module_id' => $module->id]);

        // Complete 3 out of 5 lessons (60%)
        foreach ($lessons->take(3) as $lesson) {
            LessonProgress::create([
                'enrollment_id' => $this->enrollment->id,
                'lesson_id' => $lesson->id,
                'status' => 'completed',
                'progress_percentage' => 100,
                'time_spent' => 300,
                'first_accessed_at' => now(),
                'last_accessed_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $percentage = $this->progressService->calculateCourseCompletion($this->enrollment);

        $this->assertEquals(60, $percentage);
    }

    /** @test */
    public function it_gets_total_lessons_count_correctly()
    {
        $module1 = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $module2 = CourseModule::factory()->create(['course_id' => $this->course->id]);

        CourseLesson::factory()->count(3)->create(['module_id' => $module1->id]);
        CourseLesson::factory()->count(2)->create(['module_id' => $module2->id]);

        $count = $this->progressService->getTotalLessonsCount($this->course);

        $this->assertEquals(5, $count);
    }

    /** @test */
    public function it_gets_completed_lessons_count()
    {
        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lessons = CourseLesson::factory()->count(4)->create(['module_id' => $module->id]);

        // Complete 2 lessons
        foreach ($lessons->take(2) as $lesson) {
            LessonProgress::create([
                'enrollment_id' => $this->enrollment->id,
                'lesson_id' => $lesson->id,
                'status' => 'completed',
                'progress_percentage' => 100,
                'time_spent' => 300,
                'first_accessed_at' => now(),
                'last_accessed_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $count = $this->progressService->getCompletedLessonsCount($this->enrollment);

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_gets_in_progress_lessons_count()
    {
        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lessons = CourseLesson::factory()->count(4)->create(['module_id' => $module->id]);

        // 2 completed, 1 in progress, 1 not started
        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lessons[0]->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'time_spent' => 300,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lessons[1]->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'time_spent' => 300,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lessons[2]->id,
            'status' => 'in_progress',
            'progress_percentage' => 50,
            'time_spent' => 150,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
        ]);

        $count = $this->progressService->getInProgressLessonsCount($this->enrollment);

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_gets_progress_statistics()
    {
        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lessons = CourseLesson::factory()->count(5)->create(['module_id' => $module->id]);

        // 2 completed, 1 in progress, 2 not started
        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lessons[0]->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'time_spent' => 300,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lessons[1]->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'time_spent' => 400,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lessons[2]->id,
            'status' => 'in_progress',
            'progress_percentage' => 50,
            'time_spent' => 150,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
        ]);

        $this->enrollment->update(['progress_percentage' => 40]);

        $stats = $this->progressService->getProgressStats($this->enrollment);

        $this->assertEquals(5, $stats['total_lessons']);
        $this->assertEquals(2, $stats['completed_lessons']);
        $this->assertEquals(1, $stats['in_progress_lessons']);
        $this->assertEquals(2, $stats['not_started_lessons']);
        $this->assertEquals(40, $stats['progress_percentage']);
        $this->assertEquals(850, $stats['total_time_spent']); // 300 + 400 + 150
    }

    /** @test */
    public function it_gets_user_progress_across_all_enrollments()
    {
        // Create another course and enrollment
        $course2 = Course::factory()->create(['is_published' => true]);
        $enrollment2 = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course2->id,
            'status' => 'completed',
            'progress_percentage' => 100,
        ]);

        $this->enrollment->update(['progress_percentage' => 50]);

        $module1 = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lesson1 = CourseLesson::factory()->create(['module_id' => $module1->id]);

        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lesson1->id,
            'status' => 'in_progress',
            'progress_percentage' => 50,
            'time_spent' => 200,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
        ]);

        $module2 = CourseModule::factory()->create(['course_id' => $course2->id]);
        $lesson2 = CourseLesson::factory()->create(['module_id' => $module2->id]);

        LessonProgress::create([
            'enrollment_id' => $enrollment2->id,
            'lesson_id' => $lesson2->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'time_spent' => 300,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        $stats = $this->progressService->getUserProgress($this->user);

        $this->assertEquals(2, $stats['total_courses']);
        $this->assertEquals(1, $stats['completed_courses']);
        $this->assertEquals(1, $stats['in_progress_courses']);
        $this->assertEquals(500, $stats['total_time_spent']); // 200 + 300
        $this->assertEquals(75, $stats['average_progress']); // (50 + 100) / 2
    }

    /** @test */
    public function it_gets_detailed_progress_with_module_breakdown()
    {
        $module1 = CourseModule::factory()->create([
            'course_id' => $this->course->id,
            'title' => 'Module 1',
        ]);
        $module2 = CourseModule::factory()->create([
            'course_id' => $this->course->id,
            'title' => 'Module 2',
        ]);

        $lesson1 = CourseLesson::factory()->create(['module_id' => $module1->id]);
        $lesson2 = CourseLesson::factory()->create(['module_id' => $module1->id]);
        $lesson3 = CourseLesson::factory()->create(['module_id' => $module2->id]);

        // Complete lesson 1
        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lesson1->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'time_spent' => 300,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        $this->enrollment->update(['progress_percentage' => 33.33]);

        $details = $this->progressService->getDetailedProgress($this->enrollment);

        $this->assertArrayHasKey('enrollment', $details);
        $this->assertArrayHasKey('modules', $details);
        $this->assertArrayHasKey('stats', $details);

        $this->assertCount(2, $details['modules']);

        // Check module 1 (1 of 2 lessons completed = 50%)
        $this->assertEquals('Module 1', $details['modules'][0]['module_title']);
        $this->assertEquals(2, $details['modules'][0]['total_count']);
        $this->assertEquals(1, $details['modules'][0]['completed_count']);
        $this->assertEquals(50, $details['modules'][0]['progress_percentage']);

        // Check module 2 (0 of 1 lessons completed = 0%)
        $this->assertEquals('Module 2', $details['modules'][1]['module_title']);
        $this->assertEquals(1, $details['modules'][1]['total_count']);
        $this->assertEquals(0, $details['modules'][1]['completed_count']);
        $this->assertEquals(0, $details['modules'][1]['progress_percentage']);
    }

    /** @test */
    public function it_can_recalculate_all_progress()
    {
        // Create multiple enrollments
        $user2 = User::factory()->create();
        $enrollment2 = Enrollment::factory()->create([
            'user_id' => $user2->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        $module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $lessons = CourseLesson::factory()->count(2)->create(['module_id' => $module->id]);

        // Complete 1 lesson for enrollment 1
        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lessons[0]->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'time_spent' => 300,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
            'completed_at' => now(),
        ]);

        // Complete both lessons for enrollment 2
        foreach ($lessons as $lesson) {
            LessonProgress::create([
                'enrollment_id' => $enrollment2->id,
                'lesson_id' => $lesson->id,
                'status' => 'completed',
                'progress_percentage' => 100,
                'time_spent' => 300,
                'first_accessed_at' => now(),
                'last_accessed_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $result = $this->progressService->recalculateAllProgress();

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['updated']);
        $this->assertEquals(0, $result['errors']);

        // Check that progress was updated
        $this->assertEquals(50, $this->enrollment->fresh()->progress_percentage);
        $this->assertEquals(100, $enrollment2->fresh()->progress_percentage);
        $this->assertEquals('completed', $enrollment2->fresh()->status);
    }
}
