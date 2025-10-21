<?php

namespace Tests\Feature\Integration;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseLesson;
use App\Models\Question;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\AttemptService;
use App\Services\CertificateService;
use App\Services\ContentService;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteUserWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected EnrollmentService $enrollmentService;
    protected ContentService $contentService;
    protected AssessmentService $assessmentService;
    protected AttemptService $attemptService;
    protected CertificateService $certificateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enrollmentService = app(EnrollmentService::class);
        $this->contentService = app(ContentService::class);
        $this->assessmentService = app(AssessmentService::class);
        $this->attemptService = app(AttemptService::class);
        $this->certificateService = app(CertificateService::class);
    }

    /**
     * Test complete employee learning journey from registration to certificate
     */
    public function test_complete_employee_learning_journey()
    {
        // Step 1: User Registration
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'employee',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);

        // Step 2: Browse Course Catalog
        $course = Course::factory()->create([
            'title' => 'Introduction to Laravel',
            'is_published' => true,
            'is_mandatory' => false,
        ]);

        $response = $this->actingAs($user)->get(route('catalog.index'));
        $response->assertStatus(200);
        $response->assertSee('Introduction to Laravel');

        // Step 3: View Course Details
        $response = $this->actingAs($user)->get(route('catalog.show', $course));
        $response->assertStatus(200);
        $response->assertSee($course->title);
        $response->assertSee($course->description);

        // Step 4: Enroll in Course
        $response = $this->actingAs($user)->post(route('enrollments.store', $course));
        $response->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $enrollment = $user->enrollments()->where('course_id', $course->id)->first();
        $this->assertNotNull($enrollment);

        // Step 5: Access Course Content
        $module = CourseModule::factory()->create([
            'course_id' => $course->id,
            'title' => 'Module 1: Basics',
            'order_index' => 0,
        ]);

        $lesson = CourseLesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Lesson 1: Introduction',
            'content_type' => 'video',
            'duration' => 600,
            'order_index' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('lessons.show', $lesson));
        $response->assertStatus(200);
        $response->assertSee($lesson->title);

        // Step 6: Track Progress
        $this->contentService->trackProgress($user, $lesson, [
            'time_spent' => 300,
            'last_position' => 300,
        ]);

        $progress = $user->lessonProgress()->where('lesson_id', $lesson->id)->first();
        $this->assertNotNull($progress);
        $this->assertEquals(300, $progress->time_spent);

        // Step 7: Complete Lesson
        $this->contentService->markComplete($user, $lesson);

        $progress->refresh();
        $this->assertTrue($progress->is_completed);

        // Step 8: Take Assessment
        $assessment = Assessment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Final Assessment',
            'is_published' => true,
            'passing_score' => 70,
            'max_attempts' => 3,
        ]);

        // Add questions
        $question1 = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'question_text' => 'What is Laravel?',
            'question_type' => 'multiple_choice',
            'points' => 10,
        ]);

        $question1->options()->create([
            'option_text' => 'A PHP Framework',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $question1->options()->create([
            'option_text' => 'A Database',
            'is_correct' => false,
            'order_index' => 1,
        ]);

        // Start assessment
        $response = $this->actingAs($user)->post(route('assessments.start', $assessment));
        $response->assertRedirect();

        $attempt = $user->assessmentAttempts()
            ->where('assessment_id', $assessment->id)
            ->where('status', 'in_progress')
            ->first();

        $this->assertNotNull($attempt);

        // Submit answers
        $correctOption = $question1->options()->where('is_correct', true)->first();

        $response = $this->actingAs($user)->post(route('attempts.submit', $attempt), [
            'responses' => [
                $question1->id => $correctOption->id,
            ],
        ]);

        $response->assertRedirect();

        // Step 9: View Results
        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);
        $this->assertGreaterThanOrEqual(70, $attempt->score);

        $response = $this->actingAs($user)->get(route('attempts.results', $attempt));
        $response->assertStatus(200);
        $response->assertSee('Passed');

        // Step 10: Course Completion and Certificate Generation
        $enrollment->refresh();
        $enrollment->update([
            'status' => 'completed',
            'completion_date' => now(),
            'final_score' => $attempt->score,
        ]);

        // Generate certificate
        $certificate = $this->certificateService->generate($enrollment);

        $this->assertNotNull($certificate);
        $this->assertDatabaseHas('certificates', [
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
        ]);

        // Step 11: View and Download Certificate
        $response = $this->actingAs($user)->get(route('certificates.show', $certificate));
        $response->assertStatus(200);
        $response->assertSee($certificate->certificate_number);

        // Step 12: Verify Certificate
        $response = $this->get(route('certificates.verify', $certificate->certificate_number));
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($course->title);
    }

    /**
     * Test instructor workflow: Create course, add content, create assessment
     */
    public function test_complete_instructor_workflow()
    {
        // Step 1: Login as Instructor
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->post('/login', [
            'email' => $instructor->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Step 2: Create Course
        $response = $this->actingAs($instructor)->post(route('admin.courses.store'), [
            'title' => 'Advanced PHP Programming',
            'description' => 'Learn advanced PHP concepts',
            'short_description' => 'Advanced PHP course',
            'difficulty_level' => 'advanced',
            'estimated_duration' => 1200,
            'is_published' => false,
        ]);

        $response->assertRedirect();

        $course = Course::where('title', 'Advanced PHP Programming')->first();
        $this->assertNotNull($course);
        $this->assertEquals($instructor->id, $course->created_by);

        // Step 3: Add Module
        $response = $this->actingAs($instructor)->post(route('admin.modules.store'), [
            'course_id' => $course->id,
            'title' => 'Module 1: OOP Concepts',
            'description' => 'Object-Oriented Programming',
            'order_index' => 0,
        ]);

        $response->assertRedirect();

        $module = $course->modules()->first();
        $this->assertNotNull($module);

        // Step 4: Add Lesson
        $response = $this->actingAs($instructor)->post(route('admin.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'Lesson 1: Classes and Objects',
            'content_type' => 'text',
            'content' => 'Lesson content here...',
            'duration' => 300,
            'order_index' => 0,
        ]);

        $response->assertRedirect();

        $lesson = $module->lessons()->first();
        $this->assertNotNull($lesson);

        // Step 5: Create Assessment
        $response = $this->actingAs($instructor)->post(route('admin.assessments.store'), [
            'course_id' => $course->id,
            'title' => 'OOP Quiz',
            'description' => 'Test your OOP knowledge',
            'passing_score' => 70,
            'time_limit' => 30,
            'max_attempts' => 2,
        ]);

        $response->assertRedirect();

        $assessment = Assessment::where('title', 'OOP Quiz')->first();
        $this->assertNotNull($assessment);

        // Step 6: Add Questions
        $response = $this->actingAs($instructor)->post(route('admin.questions.store'), [
            'assessment_id' => $assessment->id,
            'question_text' => 'What is encapsulation?',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'options' => [
                ['option_text' => 'Hiding data', 'is_correct' => true],
                ['option_text' => 'Showing data', 'is_correct' => false],
            ],
        ]);

        $response->assertRedirect();

        $this->assertEquals(1, $assessment->questions()->count());

        // Step 7: Publish Course
        $response = $this->actingAs($instructor)->post(route('admin.courses.publish', $course));
        $response->assertRedirect();

        $course->refresh();
        $this->assertTrue($course->is_published);

        // Step 8: View Course Analytics
        $response = $this->actingAs($instructor)->get(route('admin.courses.show', $course));
        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    /**
     * Test admin workflow: Manage users, bulk enroll, view analytics
     */
    public function test_complete_admin_workflow()
    {
        // Step 1: Login as Admin
        $admin = User::factory()->create(['role' => 'hr_admin']);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Step 2: Create New User
        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Employee',
            'email' => 'newemployee@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'employee',
        ]);

        $response->assertRedirect();

        $newUser = User::where('email', 'newemployee@example.com')->first();
        $this->assertNotNull($newUser);

        // Step 3: Create Mandatory Course
        $course = Course::factory()->create([
            'title' => 'Compliance Training',
            'is_published' => true,
            'is_mandatory' => true,
        ]);

        // Step 4: Bulk Enroll Users
        $users = User::factory()->count(5)->create(['role' => 'employee']);

        $response = $this->actingAs($admin)->post(route('admin.enrollments.bulk-enroll'), [
            'course_id' => $course->id,
            'user_ids' => $users->pluck('id')->toArray(),
            'enrollment_type' => 'assigned',
            'deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        foreach ($users as $user) {
            $this->assertDatabaseHas('course_enrollments', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrollment_type' => 'assigned',
            ]);
        }

        // Step 5: View Analytics Dashboard
        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));
        $response->assertStatus(200);

        // Step 6: Generate Report
        $response = $this->actingAs($admin)->post(route('admin.reports.store'), [
            'name' => 'Monthly Compliance Report',
            'type' => 'compliance',
            'filters' => [
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ],
        ]);

        $response->assertRedirect();

        // Step 7: View Audit Logs
        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
    }
}
