<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Course;
use App\Services\FileUploadService;
use App\Services\VideoProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Course $course;
    protected CourseModule $module;
    protected CourseLesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        // Create course structure
        $this->course = Course::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        $this->module = CourseModule::factory()->create([
            'course_id' => $this->course->id,
        ]);

        $this->lesson = CourseLesson::factory()->create([
            'module_id' => $this->module->id,
        ]);
    }

    public function test_admin_can_upload_video()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test-video.mp4', 10000, 'video/mp4');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.upload.video'), [
                'video' => $file,
                'lesson_id' => $this->lesson->id,
            ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'path',
                'url',
                'filename',
                'original_name',
                'size',
                'mime_type',
            ],
            'message',
        ]);

        // Verify file was stored
        $data = $response->json('data');
        Storage::disk('public')->assertExists($data['path']);
    }

    public function test_admin_can_upload_pdf()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test-document.pdf', 5000, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.upload.pdf'), [
                'pdf' => $file,
                'lesson_id' => $this->lesson->id,
            ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'path',
                'url',
                'filename',
            ],
        ]);

        $data = $response->json('data');
        Storage::disk('public')->assertExists($data['path']);
    }

    public function test_admin_can_upload_presentation()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test-presentation.pptx', 5000, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.upload.presentation'), [
                'presentation' => $file,
                'lesson_id' => $this->lesson->id,
            ]);

        $response->assertSuccessful();
        $data = $response->json('data');
        Storage::disk('public')->assertExists($data['path']);
    }

    public function test_video_upload_validates_file_type()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.upload.video'), [
                'video' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['video']);
    }

    public function test_video_upload_validates_file_size()
    {
        Storage::fake('public');

        // Create a file larger than allowed (500MB + 1KB)
        $file = UploadedFile::fake()->create('huge-video.mp4', 512001, 'video/mp4');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.upload.video'), [
                'video' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['video']);
    }

    public function test_employee_cannot_upload_files()
    {
        Storage::fake('public');

        $employee = User::factory()->create(['role' => 'employee']);
        $file = UploadedFile::fake()->create('test-video.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($employee)
            ->post(route('admin.upload.video'), [
                'video' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_upload_files()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test-video.mp4', 1000, 'video/mp4');

        $response = $this->post(route('admin.upload.video'), [
            'video' => $file,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_can_delete_uploaded_file()
    {
        Storage::fake('public');

        // Upload a file first
        $file = UploadedFile::fake()->create('test-video.mp4', 1000, 'video/mp4');
        
        $uploadResponse = $this->actingAs($this->admin)
            ->post(route('admin.upload.video'), [
                'video' => $file,
            ]);

        $filePath = $uploadResponse->json('data.path');

        // Verify file exists
        Storage::disk('public')->assertExists($filePath);

        // Delete the file
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.upload.delete'), [
                'file_path' => $filePath,
                'file_type' => 'video',
            ]);

        $response->assertSuccessful();

        // Verify file is deleted
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_file_upload_service_generates_unique_filenames()
    {
        Storage::fake('public');

        $service = app(FileUploadService::class);

        $file1 = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');
        $file2 = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        $result1 = $service->uploadVideo($file1);
        $result2 = $service->uploadVideo($file2);

        // Filenames should be different even though original names are the same
        $this->assertNotEquals($result1['filename'], $result2['filename']);
        $this->assertNotEquals($result1['path'], $result2['path']);
    }

    public function test_lesson_content_upload_updates_lesson()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('test-video.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.lessons.upload-content', $this->lesson), [
                'content_type' => 'video',
                'file' => $file,
            ]);

        $response->assertSuccessful();

        // Verify lesson was updated
        $this->lesson->refresh();
        $this->assertEquals('video', $this->lesson->content_type);
        $this->assertNotNull($this->lesson->content_path);
    }

    public function test_video_processing_service_checks_ffmpeg_availability()
    {
        $service = app(VideoProcessingService::class);
        
        // This should not throw an exception
        $isAvailable = $service->isFFmpegAvailable();
        
        $this->assertIsBool($isAvailable);
    }

    public function test_pdf_upload_validates_mime_type()
    {
        Storage::fake('public');

        // Try to upload a non-PDF file with .pdf extension
        $file = UploadedFile::fake()->create('fake.pdf', 100, 'text/plain');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.upload.pdf'), [
                'pdf' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_upload_progress_tracking()
    {
        $service = app(FileUploadService::class);
        $uploadId = 'test-upload-123';

        // Update progress
        $service->updateUploadProgress($uploadId, 50, 100);

        // Get progress
        $progress = $service->getUploadProgress($uploadId);

        $this->assertEquals(50, $progress['uploaded']);
        $this->assertEquals(100, $progress['total']);
        $this->assertEquals(50, $progress['percentage']);
        $this->assertEquals('uploading', $progress['status']);
    }

    public function test_upload_progress_marks_complete_at_100_percent()
    {
        $service = app(FileUploadService::class);
        $uploadId = 'test-upload-456';

        // Update to 100%
        $service->updateUploadProgress($uploadId, 100, 100);

        // Get progress
        $progress = $service->getUploadProgress($uploadId);

        $this->assertEquals(100, $progress['percentage']);
        $this->assertEquals('completed', $progress['status']);
    }
}
