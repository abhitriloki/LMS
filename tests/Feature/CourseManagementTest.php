<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\CourseService;
use App\Services\LessonService;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ========================================
    // Course CRUD Tests
    // ========================================

    /** @test */
    public function instructor_can_create_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $category = CourseCategory::factory()->create();

        $courseData = [
            'title' => 'Test Course',
            'description' => 'This is a test course description',
            'short_description' => 'Short description',
            'category_id' => $category->id,
            'difficulty_level' => 'beginner',
            'estimated_duration' => 120,
            'learning_objectives' => ['Objective 1', 'Objective 2'],
            'target_audience' => 'Developers',
            'language' => 'en',
        ];

        $response = $this->actingAs($instructor)->post(route('admin.courses.store'), $courseData);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'title' => 'Test Course',
            'category_id' => $category->id,
            'created_by' => $instructor->id,
        ]);
    }

    /** @test */
    public function course_slug_is_automatically_generated()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $service = app(CourseService::class);

        $courseData = [
            'title' => 'My Awesome Course',
            'description' => 'Description',
            'category_id' => CourseCategory::factory()->create()->id,
            'difficulty_level' => 'beginner',
        ];

        $course = $service->createCourse($courseData, $instructor);

        $this->assertEquals('my-awesome-course', $course->slug);
    }

    /** @test */
    public function duplicate_course_slugs_are_made_unique()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $service = app(CourseService::class);
        $category = CourseCategory::factory()->create();

        $courseData = [
            'title' => 'Same Title',
            'description' => 'Description',
            'category_id' => $category->id,
            'difficulty_level' => 'beginner',
        ];

        $course1 = $service->createCourse($courseData, $instructor);
        $course2 = $service->createCourse($courseData, $instructor);

        $this->assertEquals('same-title', $course1->slug);
        $this->assertEquals('same-title-1', $course2->slug);
    }

    /** @test */
    public function instructor_can_view_course_details()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $response = $this->actingAs($instructor)->get(route('admin.courses.show', $course));

        $response->assertStatus(200);
        $response->assertViewIs('admin.courses.show');
        $response->assertSee($course->title);
    }

    /** @test */
    public function instructor_can_update_own_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $updateData = [
            'title' => 'Updated Course Title',
            'description' => $course->description,
            'category_id' => $course->category_id,
            'difficulty_level' => 'advanced',
        ];

        $response = $this->actingAs($instructor)->put(route('admin.courses.update', $course), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Course Title',
            'difficulty_level' => 'advanced',
        ]);
    }

    /** @test */
    public function instructor_cannot_update_other_instructors_course()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor1->id]);

        $updateData = [
            'title' => 'Hacked Title',
            'description' => $course->description,
            'category_id' => $course->category_id,
        ];

        $response = $this->actingAs($instructor2)->put(route('admin.courses.update', $course), $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_any_course()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $updateData = [
            'title' => 'Admin Updated Title',
            'description' => $course->description,
            'category_id' => $course->category_id,
        ];

        $response = $this->actingAs($admin)->put(route('admin.courses.update', $course), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Admin Updated Title',
        ]);
    }

    /** @test */
    public function instructor_can_delete_own_course_without_enrollments()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $response = $this->actingAs($instructor)->delete(route('admin.courses.destroy', $course));

        $response->assertRedirect();
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    /** @test */
    public function cannot_delete_course_with_enrollments()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        
        // Create an enrollment
        Enrollment::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($instructor)->delete(route('admin.courses.destroy', $course));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    // ========================================
    // Course Publishing Tests
    // ========================================

    /** @test */
    public function course_can_be_published_when_valid()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id, 'is_published' => false]);
        
        // Add module and lesson to make it valid for publishing
        $module = CourseModule::factory()->forCourse($course)->create();
        CourseLesson::factory()->forModule($module)->create();

        $service = app(CourseService::class);
        $service->publishCourse($course);

        $this->assertTrue($course->fresh()->is_published);
    }

    /** @test */
    public function course_cannot_be_published_without_modules()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id, 'is_published' => false]);

        $service = app(CourseService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must have at least one module');
        
        $service->publishCourse($course);
    }

    /** @test */
    public function course_cannot_be_published_without_lessons()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id, 'is_published' => false]);
        
        // Add module but no lessons
        CourseModule::factory()->forCourse($course)->create();

        $service = app(CourseService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must have at least one lesson');
        
        $service->publishCourse($course);
    }

    /** @test */
    public function course_can_be_unpublished()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->published()->create(['created_by' => $instructor->id]);

        $service = app(CourseService::class);
        $service->unpublishCourse($course);

        $this->assertFalse($course->fresh()->is_published);
    }

    // ========================================
    // Course Cloning Tests
    // ========================================

    /** @test */
    public function course_can_be_cloned_with_modules_and_lessons()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id, 'title' => 'Original Course']);
        
        $module1 = CourseModule::factory()->forCourse($course)->create(['title' => 'Module 1', 'order_index' => 0]);
        $module2 = CourseModule::factory()->forCourse($course)->create(['title' => 'Module 2', 'order_index' => 1]);
        
        CourseLesson::factory()->forModule($module1)->create(['title' => 'Lesson 1.1', 'order_index' => 0]);
        CourseLesson::factory()->forModule($module1)->create(['title' => 'Lesson 1.2', 'order_index' => 1]);
        CourseLesson::factory()->forModule($module2)->create(['title' => 'Lesson 2.1', 'order_index' => 0]);

        $service = app(CourseService::class);
        $clonedCourse = $service->cloneCourse($course, $instructor);

        $this->assertEquals('Original Course (Copy)', $clonedCourse->title);
        $this->assertFalse($clonedCourse->is_published);
        $this->assertEquals(2, $clonedCourse->modules()->count());
        $this->assertEquals(3, $clonedCourse->getTotalLessonsCount());
        
        // Verify module titles
        $this->assertEquals('Module 1', $clonedCourse->modules()->first()->title);
        $this->assertEquals('Module 2', $clonedCourse->modules()->skip(1)->first()->title);
    }

    /** @test */
    public function cloned_course_has_unique_slug()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id, 'slug' => 'test-course']);

        $service = app(CourseService::class);
        $clonedCourse = $service->cloneCourse($course, $instructor);

        $this->assertNotEquals($course->slug, $clonedCourse->slug);
        $this->assertStringContainsString('copy', $clonedCourse->slug);
    }

    // ========================================
    // Prerequisite Validation Tests
    // ========================================

    /** @test */
    public function course_can_have_valid_prerequisites()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $prereqCourse = Course::factory()->create();
        
        $service = app(CourseService::class);
        $courseData = [
            'title' => 'Advanced Course',
            'description' => 'Description',
            'category_id' => CourseCategory::factory()->create()->id,
            'difficulty_level' => 'advanced',
            'prerequisites' => [$prereqCourse->id],
        ];

        $course = $service->createCourse($courseData, $instructor);

        $this->assertTrue($course->hasPrerequisites());
        $this->assertContains($prereqCourse->id, $course->prerequisites);
    }

    /** @test */
    public function invalid_prerequisite_course_ids_are_rejected()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $service = app(CourseService::class);

        $courseData = [
            'title' => 'Advanced Course',
            'description' => 'Description',
            'category_id' => CourseCategory::factory()->create()->id,
            'difficulty_level' => 'advanced',
            'prerequisites' => [99999], // Non-existent course ID
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid prerequisite courses');
        
        $service->createCourse($courseData, $instructor);
    }

    /** @test */
    public function course_cannot_be_its_own_prerequisite()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        
        $service = app(CourseService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be its own prerequisite');
        
        $service->validatePrerequisites([$course->id], $course->id);
    }

    /** @test */
    public function circular_prerequisite_dependencies_are_detected()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        
        // Create Course A
        $courseA = Course::factory()->create(['created_by' => $instructor->id]);
        
        // Create Course B with A as prerequisite
        $courseB = Course::factory()->withPrerequisites([$courseA->id])->create(['created_by' => $instructor->id]);
        
        $service = app(CourseService::class);

        // Try to make A require B (circular dependency)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        $service->validatePrerequisites([$courseB->id], $courseA->id);
    }

    /** @test */
    public function user_meets_prerequisites_when_completed()
    {
        $user = User::factory()->create();
        $prereqCourse = Course::factory()->create();
        $course = Course::factory()->withPrerequisites([$prereqCourse->id])->create();
        
        // User completes prerequisite course
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $prereqCourse->id,
            'status' => 'completed',
        ]);

        $service = app(CourseService::class);
        $meetsPre requisites = $service->userMeetsPrerequisites($course, $user);

        $this->assertTrue($meetsPrerequisites);
    }

    /** @test */
    public function user_does_not_meet_prerequisites_when_not_completed()
    {
        $user = User::factory()->create();
        $prereqCourse = Course::factory()->create();
        $course = Course::factory()->withPrerequisites([$prereqCourse->id])->create();

        $service = app(CourseService::class);
        $meetsPrerequisites = $service->userMeetsPrerequisites($course, $user);

        $this->assertFalse($meetsPrerequisites);
    }

    /** @test */
    public function missing_prerequisites_can_be_retrieved()
    {
        $user = User::factory()->create();
        $prereq1 = Course::factory()->create(['title' => 'Prereq 1']);
        $prereq2 = Course::factory()->create(['title' => 'Prereq 2']);
        $course = Course::factory()->withPrerequisites([$prereq1->id, $prereq2->id])->create();
        
        // User completes only first prerequisite
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $prereq1->id,
            'status' => 'completed',
        ]);

        $service = app(CourseService::class);
        $missing = $service->getMissingPrerequisites($course, $user);

        $this->assertCount(1, $missing);
        $this->assertEquals($prereq2->id, $missing[0]['id']);
    }

    // ========================================
    // Module Management Tests
    // ========================================

    /** @test */
    public function module_can_be_created_for_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $moduleData = [
            'title' => 'Module 1',
            'description' => 'First module',
        ];

        $service = app(ModuleService::class);
        $module = $service->createModule($course, $moduleData);

        $this->assertDatabaseHas('course_modules', [
            'course_id' => $course->id,
            'title' => 'Module 1',
        ]);
        $this->assertEquals(0, $module->order_index);
    }

    /** @test */
    public function module_order_index_is_auto_incremented()
    {
        $course = Course::factory()->create();
        $service = app(ModuleService::class);

        $module1 = $service->createModule($course, ['title' => 'Module 1']);
        $module2 = $service->createModule($course, ['title' => 'Module 2']);
        $module3 = $service->createModule($course, ['title' => 'Module 3']);

        $this->assertEquals(0, $module1->order_index);
        $this->assertEquals(1, $module2->order_index);
        $this->assertEquals(2, $module3->order_index);
    }

    /** @test */
    public function module_can_be_updated()
    {
        $module = CourseModule::factory()->create(['title' => 'Old Title']);
        $service = app(ModuleService::class);

        $updatedModule = $service->updateModule($module, [
            'title' => 'New Title',
            'description' => 'Updated description',
        ]);

        $this->assertEquals('New Title', $updatedModule->title);
        $this->assertEquals('Updated description', $updatedModule->description);
    }

    /** @test */
    public function module_can_be_deleted()
    {
        $module = CourseModule::factory()->create();
        $moduleId = $module->id;
        
        $service = app(ModuleService::class);
        $service->deleteModule($module);

        $this->assertDatabaseMissing('course_modules', ['id' => $moduleId]);
    }

    /** @test */
    public function deleting_module_deletes_its_lessons()
    {
        $module = CourseModule::factory()->create();
        $lesson1 = CourseLesson::factory()->forModule($module)->create();
        $lesson2 = CourseLesson::factory()->forModule($module)->create();
        
        $service = app(ModuleService::class);
        $service->deleteModule($module);

        $this->assertDatabaseMissing('course_lessons', ['id' => $lesson1->id]);
        $this->assertDatabaseMissing('course_lessons', ['id' => $lesson2->id]);
    }

    /** @test */
    public function modules_can_be_reordered()
    {
        $course = Course::factory()->create();
        $module1 = CourseModule::factory()->forCourse($course)->create(['order_index' => 0]);
        $module2 = CourseModule::factory()->forCourse($course)->create(['order_index' => 1]);
        $module3 = CourseModule::factory()->forCourse($course)->create(['order_index' => 2]);

        $service = app(ModuleService::class);
        
        // Reorder: module3, module1, module2
        $service->reorderModules($course, [$module3->id, $module1->id, $module2->id]);

        $this->assertEquals(0, $module3->fresh()->order_index);
        $this->assertEquals(1, $module1->fresh()->order_index);
        $this->assertEquals(2, $module2->fresh()->order_index);
    }

    /** @test */
    public function module_can_be_duplicated()
    {
        $module = CourseModule::factory()->create(['title' => 'Original Module']);
        $lesson1 = CourseLesson::factory()->forModule($module)->create();
        $lesson2 = CourseLesson::factory()->forModule($module)->create();

        $service = app(ModuleService::class);
        $duplicate = $service->duplicateModule($module);

        $this->assertEquals('Original Module (Copy)', $duplicate->title);
        $this->assertEquals(2, $duplicate->lessons()->count());
        $this->assertNotEquals($module->id, $duplicate->id);
    }

    // ========================================
    // Lesson Management Tests
    // ========================================

    /** @test */
    public function lesson_can_be_created_for_module()
    {
        $module = CourseModule::factory()->create();

        $lessonData = [
            'title' => 'Lesson 1',
            'description' => 'First lesson',
            'content_type' => 'video',
            'content_path' => 'courses/videos/lesson1.mp4',
            'duration' => 30,
        ];

        $service = app(LessonService::class);
        $lesson = $service->createLesson($module, $lessonData);

        $this->assertDatabaseHas('course_lessons', [
            'module_id' => $module->id,
            'title' => 'Lesson 1',
            'content_type' => 'video',
        ]);
        $this->assertEquals(0, $lesson->order_index);
    }

    /** @test */
    public function lesson_order_index_is_auto_incremented()
    {
        $module = CourseModule::factory()->create();
        $service = app(LessonService::class);

        $lesson1 = $service->createLesson($module, ['title' => 'Lesson 1', 'content_type' => 'video']);
        $lesson2 = $service->createLesson($module, ['title' => 'Lesson 2', 'content_type' => 'pdf']);
        $lesson3 = $service->createLesson($module, ['title' => 'Lesson 3', 'content_type' => 'text']);

        $this->assertEquals(0, $lesson1->order_index);
        $this->assertEquals(1, $lesson2->order_index);
        $this->assertEquals(2, $lesson3->order_index);
    }

    /** @test */
    public function lesson_can_be_updated()
    {
        $lesson = CourseLesson::factory()->create(['title' => 'Old Title']);
        $service = app(LessonService::class);

        $updatedLesson = $service->updateLesson($lesson, [
            'title' => 'New Title',
            'duration' => 45,
        ]);

        $this->assertEquals('New Title', $updatedLesson->title);
        $this->assertEquals(45, $updatedLesson->duration);
    }

    /** @test */
    public function lesson_can_be_deleted()
    {
        $lesson = CourseLesson::factory()->create();
        $lessonId = $lesson->id;
        
        $service = app(LessonService::class);
        $service->deleteLesson($lesson);

        $this->assertDatabaseMissing('course_lessons', ['id' => $lessonId]);
    }

    /** @test */
    public function lessons_can_be_reordered()
    {
        $module = CourseModule::factory()->create();
        $lesson1 = CourseLesson::factory()->forModule($module)->create(['order_index' => 0]);
        $lesson2 = CourseLesson::factory()->forModule($module)->create(['order_index' => 1]);
        $lesson3 = CourseLesson::factory()->forModule($module)->create(['order_index' => 2]);

        $service = app(LessonService::class);
        
        // Reorder: lesson2, lesson3, lesson1
        $service->reorderLessons($module, [$lesson2->id, $lesson3->id, $lesson1->id]);

        $this->assertEquals(0, $lesson2->fresh()->order_index);
        $this->assertEquals(1, $lesson3->fresh()->order_index);
        $this->assertEquals(2, $lesson1->fresh()->order_index);
    }

    /** @test */
    public function lesson_can_be_duplicated()
    {
        $lesson = CourseLesson::factory()->create(['title' => 'Original Lesson']);

        $service = app(LessonService::class);
        $duplicate = $service->duplicateLesson($lesson);

        $this->assertEquals('Original Lesson (Copy)', $duplicate->title);
        $this->assertNotEquals($lesson->id, $duplicate->id);
        $this->assertEquals($lesson->module_id, $duplicate->module_id);
    }

    /** @test */
    public function lesson_can_be_moved_to_another_module()
    {
        $module1 = CourseModule::factory()->create();
        $module2 = CourseModule::factory()->create(['course_id' => $module1->course_id]);
        $lesson = CourseLesson::factory()->forModule($module1)->create();

        $service = app(LessonService::class);
        $movedLesson = $service->moveLesson($lesson, $module2);

        $this->assertEquals($module2->id, $movedLesson->module_id);
    }

    /** @test */
    public function lesson_content_can_be_uploaded()
    {
        $lesson = CourseLesson::factory()->create(['content_path' => null]);
        $file = UploadedFile::fake()->create('video.mp4', 1024);

        $service = app(LessonService::class);
        $updatedLesson = $service->uploadContent($lesson, $file, 'video');

        $this->assertNotNull($updatedLesson->content_path);
        $this->assertEquals('video', $updatedLesson->content_type);
        Storage::disk('public')->assertExists($updatedLesson->content_path);
    }

    /** @test */
    public function old_content_is_deleted_when_uploading_new_content()
    {
        Storage::disk('public')->put('courses/videos/old-video.mp4', 'old content');
        $lesson = CourseLesson::factory()->create(['content_path' => 'courses/videos/old-video.mp4']);
        
        $newFile = UploadedFile::fake()->create('new-video.mp4', 1024);

        $service = app(LessonService::class);
        $service->uploadContent($lesson, $newFile, 'video');

        Storage::disk('public')->assertMissing('courses/videos/old-video.mp4');
    }

    /** @test */
    public function deleting_lesson_removes_content_file()
    {
        Storage::disk('public')->put('courses/videos/test-video.mp4', 'test content');
        $lesson = CourseLesson::factory()->create(['content_path' => 'courses/videos/test-video.mp4']);

        $service = app(LessonService::class);
        $service->deleteLesson($lesson);

        Storage::disk('public')->assertMissing('courses/videos/test-video.mp4');
    }
}
