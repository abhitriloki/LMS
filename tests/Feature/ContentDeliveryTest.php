<?php

namespace Tests\Feature;

use App\Jobs\UpdateEnrollmentProgressJob;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\ContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Course $course;
    protected CourseModule $module;
    protected CourseLesson $lesson;
    protected Enrollment $enrollment;
    protected ContentService $contentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->course = Course::factory()->create(['is_published' => true]);
        $this->module = CourseModule::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = CourseLesson::factory()->create([
            'module_id' => $this->module->id,
            'content_type' => 'video',
            'content_url' => 'https://example.com/video.mp4',
        ]);
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->contentService = app(ContentService::class);
    }

    /** @test */
    public function it_can_get_lesson_content_for_enrolled_user()
    {
        $data = $this->contentService->getLesson($this->lesson->id, $this->user);

        $this->assertArrayHasKey('lesson', $data);
        $this->assertArrayHasKey('course', $data);
        $this->assertArrayHasKey('enrollment', $data);
        $this->assertArrayHasKey('progress', $data);
        $this->assertArrayHasKey('content_url', $data);

        $this->assertEquals($this->lesson->id, $data['lesson']->id);
        $this->assertEquals($this->course->id, $data['course']->id);
        $this->assertEquals($this->enrollment->id, $data['enrollment']->id);
    }

    /** @test */
    public function it_throws_exception_when_user_not_enrolled()
    {
        $unenrolledUser = User::factory()->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->contentService->getLesson($this->lesson->id, $unenrolledUser);
    }

    /** @test */
    public function it_creates_lesson_progress_on_first_access()
    {
        $this->assertDatabaseMissing('lesson_progress', [
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
        ]);

        $data = $this->contentService->getLesson($this->lesson->id, $this->user);

        $this->assertDatabaseHas('lesson_progress', [
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'not_started',
            'progress_percentage' => 0,
        ]);
    }

    /** @test */
    public function it_updates_last_accessed_time_on_lesson_access()
    {
        $data = $this->contentService->getLesson($this->lesson->id, $this->user);

        $progress = $data['progress'];
        $this->assertNotNull($progress->last_accessed_at);
        $this->assertNotNull($this->enrollment->fresh()->last_accessed_at);
    }

    /** @test */
    public function it_can_track_lesson_progress()
    {
        Queue::fake();

        $progressData = [
            'progress_percentage' => 50,
            'position' => 120,
            'time_spent' => 120,
        ];

        $progress = $this->contentService->trackProgress($this->user, $this->lesson, $progressData);

        $this->assertEquals(50, $progress->progress_percentage);
        $this->assertEquals(120, $progress->last_position);
        $this->assertEquals(120, $progress->time_spent);
        $this->assertEquals('in_progress', $progress->status);

        Queue::assertPushed(UpdateEnrollmentProgressJob::class);
    }

    /** @test */
    public function it_updates_progress_status_based_on_percentage()
    {
        // Create initial progress
        $this->contentService->getLesson($this->lesson->id, $this->user);

        // Update to in_progress
        $progress = $this->contentService->trackProgress($this->user, $this->lesson, [
            'progress_percentage' => 50,
        ]);
        $this->assertEquals('in_progress', $progress->status);

        // Update to completed
        $progress = $this->contentService->trackProgress($this->user, $this->lesson, [
            'progress_percentage' => 100,
        ]);
        $this->assertEquals('completed', $progress->status);
    }

    /** @test */
    public function it_can_bookmark_position_in_content()
    {
        $position = 300;

        $progress = $this->contentService->bookmarkPosition($this->user, $this->lesson, $position);

        $this->assertEquals($position, $progress->last_position);
        $this->assertNotNull($progress->last_accessed_at);
    }

    /** @test */
    public function it_can_mark_lesson_as_complete()
    {
        Queue::fake();

        $progress = $this->contentService->markComplete($this->user, $this->lesson);

        $this->assertEquals('completed', $progress->status);
        $this->assertEquals(100, $progress->progress_percentage);
        $this->assertNotNull($progress->completed_at);

        Queue::assertPushed(UpdateEnrollmentProgressJob::class);
    }

    /** @test */
    public function it_dispatches_progress_update_job_when_tracking_progress()
    {
        Queue::fake();

        $this->contentService->trackProgress($this->user, $this->lesson, [
            'progress_percentage' => 75,
        ]);

        Queue::assertPushed(UpdateEnrollmentProgressJob::class, function ($job) {
            return $job->enrollment->id === $this->enrollment->id;
        });
    }

    /** @test */
    public function it_dispatches_progress_update_job_when_marking_complete()
    {
        Queue::fake();

        $this->contentService->markComplete($this->user, $this->lesson);

        Queue::assertPushed(UpdateEnrollmentProgressJob::class, function ($job) {
            return $job->enrollment->id === $this->enrollment->id;
        });
    }

    /** @test */
    public function it_returns_null_download_url_for_non_downloadable_content()
    {
        $this->lesson->update(['is_downloadable' => false]);

        $url = $this->contentService->getDownloadUrl($this->lesson, $this->user);

        $this->assertNull($url);
    }

    /** @test */
    public function it_returns_download_url_for_downloadable_content()
    {
        Storage::fake('local');
        Storage::put('lessons/test.pdf', 'test content');

        $this->lesson->update([
            'is_downloadable' => true,
            'content_path' => 'lessons/test.pdf',
        ]);

        $url = $this->contentService->getDownloadUrl($this->lesson, $this->user);

        $this->assertNotNull($url);
        $this->assertStringContainsString('lessons/test.pdf', $url);
    }

    /** @test */
    public function it_returns_null_download_url_for_unenrolled_user()
    {
        $unenrolledUser = User::factory()->create();

        $this->lesson->update(['is_downloadable' => true]);

        $url = $this->contentService->getDownloadUrl($this->lesson, $unenrolledUser);

        $this->assertNull($url);
    }

    /** @test */
    public function it_can_get_next_lesson_in_same_module()
    {
        $nextLesson = CourseLesson::factory()->create([
            'module_id' => $this->module->id,
            'order_index' => $this->lesson->order_index + 1,
        ]);

        $result = $this->contentService->getNextLesson($this->lesson);

        $this->assertNotNull($result);
        $this->assertEquals($nextLesson->id, $result->id);
    }

    /** @test */
    public function it_can_get_next_lesson_in_next_module()
    {
        $nextModule = CourseModule::factory()->create([
            'course_id' => $this->course->id,
            'order_index' => $this->module->order_index + 1,
        ]);

        $nextLesson = CourseLesson::factory()->create([
            'module_id' => $nextModule->id,
            'order_index' => 1,
        ]);

        $result = $this->contentService->getNextLesson($this->lesson);

        $this->assertNotNull($result);
        $this->assertEquals($nextLesson->id, $result->id);
    }

    /** @test */
    public function it_returns_null_when_no_next_lesson_exists()
    {
        $result = $this->contentService->getNextLesson($this->lesson);

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_get_previous_lesson_in_same_module()
    {
        $previousLesson = CourseLesson::factory()->create([
            'module_id' => $this->module->id,
            'order_index' => $this->lesson->order_index - 1,
        ]);

        $result = $this->contentService->getPreviousLesson($this->lesson);

        $this->assertNotNull($result);
        $this->assertEquals($previousLesson->id, $result->id);
    }

    /** @test */
    public function it_can_get_previous_lesson_in_previous_module()
    {
        $previousModule = CourseModule::factory()->create([
            'course_id' => $this->course->id,
            'order_index' => $this->module->order_index - 1,
        ]);

        $previousLesson = CourseLesson::factory()->create([
            'module_id' => $previousModule->id,
            'order_index' => 5,
        ]);

        $result = $this->contentService->getPreviousLesson($this->lesson);

        $this->assertNotNull($result);
        $this->assertEquals($previousLesson->id, $result->id);
    }

    /** @test */
    public function it_returns_null_when_no_previous_lesson_exists()
    {
        $result = $this->contentService->getPreviousLesson($this->lesson);

        $this->assertNull($result);
    }

    /** @test */
    public function user_can_view_lesson_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('lessons.show', $this->lesson->id));

        $response->assertOk();
        $response->assertViewIs('lessons.show');
        $response->assertViewHas('lesson');
        $response->assertViewHas('course');
        $response->assertViewHas('enrollment');
        $response->assertViewHas('progress');
    }

    /** @test */
    public function unauthenticated_user_cannot_view_lesson()
    {
        $response = $this->get(route('lessons.show', $this->lesson->id));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function user_can_update_lesson_progress_via_api()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lessons.progress.update', $this->lesson->id), [
                'progress_percentage' => 60,
                'position' => 180,
                'time_spent' => 180,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Progress updated successfully',
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'progress_percentage' => 60,
            'last_position' => 180,
        ]);
    }

    /** @test */
    public function progress_update_validates_input()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lessons.progress.update', $this->lesson->id), [
                'progress_percentage' => 150, // Invalid: > 100
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('progress_percentage');
    }

    /** @test */
    public function user_can_save_bookmark_via_api()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lessons.bookmark.save', $this->lesson->id), [
                'position' => 250,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Bookmark saved successfully',
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'last_position' => 250,
        ]);
    }

    /** @test */
    public function user_can_retrieve_bookmark_via_api()
    {
        // Create progress with bookmark
        LessonProgress::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'in_progress',
            'progress_percentage' => 40,
            'last_position' => 200,
            'time_spent' => 200,
            'first_accessed_at' => now(),
            'last_accessed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('lessons.bookmark.get', $this->lesson->id));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'last_position' => 200,
                'progress_percentage' => 40,
            ],
        ]);
    }

    /** @test */
    public function user_can_mark_lesson_complete_via_api()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lessons.complete', $this->lesson->id));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Lesson marked as complete',
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'completed',
            'progress_percentage' => 100,
        ]);
    }

    /** @test */
    public function user_can_download_lesson_content_if_downloadable()
    {
        $this->lesson->update([
            'is_downloadable' => true,
            'content_url' => 'https://example.com/video.mp4',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('lessons.download', $this->lesson->id));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => ['download_url'],
        ]);
    }

    /** @test */
    public function user_cannot_download_non_downloadable_content()
    {
        $this->lesson->update(['is_downloadable' => false]);

        $response = $this->actingAs($this->user)
            ->getJson(route('lessons.download', $this->lesson->id));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Content is not downloadable',
        ]);
    }

    /** @test */
    public function bookmark_validation_requires_position()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lessons.bookmark.save', $this->lesson->id), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('position');
    }

    /** @test */
    public function bookmark_position_must_be_non_negative()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('lessons.bookmark.save', $this->lesson->id), [
                'position' => -10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('position');
    }
}
