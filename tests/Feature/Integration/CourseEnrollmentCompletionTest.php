<?php

namespace Tests\Feature\Integration;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Question;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\CertificateService;
use App\Services\ContentService;
use App\Services\EnrollmentService;
use App\Services\ProgressTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected EnrollmentService $enrollmentService;
    protected ContentService $contentService;
    protected ProgressTrackingService $progressService;
    protected AttemptService $attemptService;
    protected CertificateService $certificateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enrollmentService = app(EnrollmentService::class);
        $this->contentService = app(ContentService::class);
        $this->progressService = app(ProgressTrackingService::class);
        $this->attemptService = app(AttemptService::class);
        $this->certificateService = app(CertificateService::class);
    }

    /**
     * Test complete course enrollment and completion flow
     */
    public function test_user_can_enroll_complete_course_and_receive_certificate()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = $this->createCompleteCoursewithContent();

        // Enroll in course
        $enrollment = $this->enrollmentService->enroll($user, $course);

        $this->assertNotNull($enrollment);
        $this->assertEquals('active', $enrollment->status);
        $this->assertEquals(0, $enrollment->progress_percentage);

        // Complete all lessons
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                // Track progress
                $this->contentService->trackProgress($user, $lesson, [
                    'time_spent' => $lesson->duration,
                    'last_position' => $lesson->duration,
                ]);

                // Mark as complete
                $this->contentService->markComplete($user, $lesson);

                $progress = $user->lessonProgress()
                    ->where('lesson_id', $lesson->id)
                    ->first();

                $this->assertTrue($progress->is_completed);
            }
        }

        // Update enrollment progress
        $this->progressService->updateProgress($enrollment);
        $enrollment->refresh();

        $this->assertEquals(100, $enrollment->progress_percentage);

        // Complete assessment
        $assessment = $course->assessments()->first();
        $attempt = $this->attemptService->startAttempt($assessment, $user);

        // Submit correct answers
        $responses = [];
        foreach ($assessment->questions as $question) {
            $correctOption = $question->options()->where('is_correct', true)->first();
            $responses[$question->id] = $correctOption->id;
        }

        $this->attemptService->submitAttempt($attempt, $responses);
        $attempt->refresh();

        $this->assertEquals('completed', $attempt->status);
        $this->assertGreaterThanOrEqual($assessment->passing_score, $attempt->score);

        // Mark enrollment as completed
        $enrollment->update([
            'status' => 'completed',
            'completion_date' => now(),
            'final_score' => $attempt->score,
        ]);

        // Generate certificate
        $certificate = $this->certificateService->generate($enrollment);

        $this->assertNotNull($certificate);
        $this->assertEquals($user->id, $certificate->user_id);
        $this->assertEquals($enrollment->id, $certificate->enrollment_id);
        $this->assertNotNull($certificate->certificate_number);
        $this->assertNotNull($certificate->issued_at);
    }

    /**
     * Test enrollment with prerequisites
     */
    public function test_user_must_complete_prerequisites_before_enrolling()
    {
        $user = User::factory()->create(['role' => 'employee']);

        // Create prerequisite course
        $prerequisiteCourse = Course::factory()->create([
            'title' => 'Prerequisite Course',
            'is_published' => true,
        ]);

        // Create main course with prerequisite
        $mainCourse = Course::factory()->create([
            'title' => 'Advanced Course',
            'is_published' => true,
            'prerequisites' => [$prerequisiteCourse->id],
        ]);

        // Try to enroll without completing prerequisite
        try {
            $this->enrollmentService->enroll($user, $mainCourse);
            $this->fail('Should not be able to enroll without completing prerequisites');
        } catch (\Exception $e) {
            $this->assertStringContainsString('prerequisite', strtolower($e->getMessage()));
        }

        // Complete prerequisite course
        $prereqEnrollment = $this->enrollmentService->enroll($user, $prerequisiteCourse);
        $prereqEnrollment->update([
            'status' => 'completed',
            'completion_date' => now(),
        ]);

        // Now should be able to enroll
        $mainEnrollment = $this->enrollmentService->enroll($user, $mainCourse);
        $this->assertNotNull($mainEnrollment);
        $this->assertEquals('active', $mainEnrollment->status);
    }

    /**
     * Test mandatory course auto-enrollment
     */
    public function test_mandatory_courses_auto_enroll_new_users()
    {
        // Create mandatory courses
        $mandatoryCourse1 = Course::factory()->create([
            'title' => 'Safety Training',
            'is_published' => true,
            'is_mandatory' => true,
        ]);

        $mandatoryCourse2 = Course::factory()->create([
            'title' => 'Ethics Training',
            'is_published' => true,
            'is_mandatory' => true,
        ]);

        // Create new user
        $user = User::factory()->create(['role' => 'employee']);

        // Auto-enroll in mandatory courses
        $enrolledCount = $this->enrollmentService->autoEnrollMandatoryCourses($user);

        $this->assertEquals(2, $enrolledCount);

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $mandatoryCourse1->id,
            'enrollment_type' => 'mandatory',
        ]);

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $mandatoryCourse2->id,
            'enrollment_type' => 'mandatory',
        ]);
    }

    /**
     * Test progress tracking throughout course
     */
    public function test_progress_is_tracked_accurately_throughout_course()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = $this->createCompleteCoursewithContent();

        $enrollment = $this->enrollmentService->enroll($user, $course);

        // Initially 0% progress
        $this->assertEquals(0, $enrollment->progress_percentage);

        // Complete first lesson
        $firstLesson = $course->modules->first()->lessons->first();
        $this->contentService->markComplete($user, $firstLesson);
        $this->progressService->updateProgress($enrollment);
        $enrollment->refresh();

        $this->assertGreaterThan(0, $enrollment->progress_percentage);
        $this->assertLessThan(100, $enrollment->progress_percentage);

        // Complete all lessons in first module
        foreach ($course->modules->first()->lessons as $lesson) {
            $this->contentService->markComplete($user, $lesson);
        }

        $this->progressService->updateProgress($enrollment);
        $enrollment->refresh();

        $expectedProgress = (1 / $course->modules->count()) * 100;
        $this->assertEquals($expectedProgress, $enrollment->progress_percentage);

        // Complete all lessons
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                $this->contentService->markComplete($user, $lesson);
            }
        }

        $this->progressService->updateProgress($enrollment);
        $enrollment->refresh();

        $this->assertEquals(100, $enrollment->progress_percentage);
    }

    /**
     * Test course completion with deadline
     */
    public function test_course_completion_before_deadline()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $deadline = now()->addDays(30);
        $enrollment = $this->enrollmentService->enroll($user, $course, 'assigned', null, $deadline);

        $this->assertEquals($deadline->format('Y-m-d'), $enrollment->deadline->format('Y-m-d'));

        // Complete course before deadline
        $enrollment->update([
            'status' => 'completed',
            'completion_date' => now(),
        ]);

        $this->assertTrue($enrollment->completion_date->lessThan($enrollment->deadline));
    }

    /**
     * Test overdue course handling
     */
    public function test_overdue_courses_are_identified()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        // Create enrollment with past deadline
        $enrollment = $this->enrollmentService->enroll(
            $user,
            $course,
            'assigned',
            null,
            now()->subDays(5)
        );

        $overdueEnrollments = $this->enrollmentService->getOverdueEnrollments($user);

        $this->assertCount(1, $overdueEnrollments);
        $this->assertEquals($enrollment->id, $overdueEnrollments->first()->id);
    }

    /**
     * Test course re-enrollment after completion
     */
    public function test_user_can_reenroll_in_completed_course()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        // First enrollment and completion
        $firstEnrollment = $this->enrollmentService->enroll($user, $course);
        $firstEnrollment->update([
            'status' => 'completed',
            'completion_date' => now(),
        ]);

        // Re-enroll
        $secondEnrollment = $this->enrollmentService->enroll($user, $course);

        $this->assertNotEquals($firstEnrollment->id, $secondEnrollment->id);
        $this->assertEquals('active', $secondEnrollment->status);
        $this->assertEquals(0, $secondEnrollment->progress_percentage);
    }

    /**
     * Test bulk enrollment by admin
     */
    public function test_admin_can_bulk_enroll_users_with_deadline()
    {
        $admin = User::factory()->create(['role' => 'hr_admin']);
        $users = User::factory()->count(10)->create(['role' => 'employee']);
        $course = Course::factory()->create(['is_published' => true]);

        $deadline = now()->addDays(60);

        $results = $this->enrollmentService->bulkEnroll(
            $users,
            $course,
            'assigned',
            $admin,
            $deadline
        );

        $this->assertCount(10, $results['success']);
        $this->assertCount(0, $results['failed']);

        foreach ($users as $user) {
            $enrollment = $user->enrollments()->where('course_id', $course->id)->first();
            $this->assertNotNull($enrollment);
            $this->assertEquals('assigned', $enrollment->enrollment_type);
            $this->assertEquals($admin->id, $enrollment->enrolled_by);
            $this->assertEquals($deadline->format('Y-m-d'), $enrollment->deadline->format('Y-m-d'));
        }
    }

    /**
     * Helper method to create a complete course with content
     */
    protected function createCompleteCoursewithContent(): Course
    {
        $course = Course::factory()->create(['is_published' => true]);

        // Create 2 modules
        for ($i = 0; $i < 2; $i++) {
            $module = CourseModule::factory()->create([
                'course_id' => $course->id,
                'title' => "Module " . ($i + 1),
                'order_index' => $i,
            ]);

            // Create 3 lessons per module
            for ($j = 0; $j < 3; $j++) {
                CourseLesson::factory()->create([
                    'module_id' => $module->id,
                    'title' => "Lesson " . ($j + 1),
                    'content_type' => 'video',
                    'duration' => 600,
                    'order_index' => $j,
                ]);
            }
        }

        // Create assessment
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'is_published' => true,
            'passing_score' => 70,
        ]);

        // Add questions
        for ($i = 0; $i < 5; $i++) {
            $question = Question::factory()->create([
                'assessment_id' => $assessment->id,
                'question_type' => 'multiple_choice',
                'points' => 20,
            ]);

            $question->options()->create([
                'option_text' => 'Correct Answer',
                'is_correct' => true,
                'order_index' => 0,
            ]);

            $question->options()->create([
                'option_text' => 'Wrong Answer',
                'is_correct' => false,
                'order_index' => 1,
            ]);
        }

        return $course->fresh(['modules.lessons', 'assessments.questions.options']);
    }
}
