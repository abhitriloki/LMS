<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CourseService
{
    public function __construct(
        protected CourseRepositoryInterface $courseRepository
    ) {}

    /**
     * Create a new course
     */
    public function createCourse(array $data, User $creator): Course
    {
        try {
            DB::beginTransaction();

            // Generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Ensure slug is unique
            $data['slug'] = $this->ensureUniqueSlug($data['slug']);

            // Set creator
            $data['created_by'] = $creator->id;

            // Set defaults
            $data['is_published'] = $data['is_published'] ?? false;
            $data['is_mandatory'] = $data['is_mandatory'] ?? false;
            $data['is_featured'] = $data['is_featured'] ?? false;

            // Validate prerequisites if provided
            if (isset($data['prerequisites']) && !empty($data['prerequisites'])) {
                $this->validatePrerequisites($data['prerequisites']);
            }

            $course = $this->courseRepository->create($data);

            DB::commit();

            Log::info('Course created', [
                'course_id' => $course->id,
                'title' => $course->title,
                'creator_id' => $creator->id
            ]);

            return $course;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create course', [
                'error' => $e->getMessage(),
                'creator_id' => $creator->id
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing course
     */
    public function updateCourse(Course $course, array $data): Course
    {
        try {
            DB::beginTransaction();

            // Update slug if title changed
            if (isset($data['title']) && $data['title'] !== $course->title) {
                $newSlug = Str::slug($data['title']);
                if ($newSlug !== $course->slug) {
                    $data['slug'] = $this->ensureUniqueSlug($newSlug, $course->id);
                }
            }

            // Validate prerequisites if provided
            if (isset($data['prerequisites']) && !empty($data['prerequisites'])) {
                $this->validatePrerequisites($data['prerequisites'], $course->id);
            }

            $course = $this->courseRepository->update($course, $data);

            DB::commit();

            Log::info('Course updated', [
                'course_id' => $course->id,
                'title' => $course->title
            ]);

            return $course;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Publish a course
     */
    public function publishCourse(Course $course): void
    {
        try {
            // Validate course is ready for publishing
            $this->validateCourseForPublishing($course);

            $this->courseRepository->publish($course);

            Log::info('Course published', [
                'course_id' => $course->id,
                'title' => $course->title
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to publish course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Unpublish a course
     */
    public function unpublishCourse(Course $course): void
    {
        try {
            $this->courseRepository->unpublish($course);

            Log::info('Course unpublished', [
                'course_id' => $course->id,
                'title' => $course->title
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to unpublish course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clone a course
     */
    public function cloneCourse(Course $course, User $creator = null): Course
    {
        try {
            DB::beginTransaction();

            // Prepare cloned course data
            $clonedData = $course->toArray();
            
            // Remove unique fields
            unset($clonedData['id'], $clonedData['created_at'], $clonedData['updated_at']);
            
            // Update title and slug
            $clonedData['title'] = $clonedData['title'] . ' (Copy)';
            $clonedData['slug'] = $this->ensureUniqueSlug(Str::slug($clonedData['title']));
            
            // Set as unpublished
            $clonedData['is_published'] = false;
            
            // Set creator
            $clonedData['created_by'] = $creator ? $creator->id : $course->created_by;

            // Create the cloned course
            $clonedCourse = $this->courseRepository->create($clonedData);

            // Clone modules and lessons
            foreach ($course->modules as $module) {
                $clonedModule = $clonedCourse->modules()->create([
                    'title' => $module->title,
                    'description' => $module->description,
                    'order_index' => $module->order_index,
                ]);

                // Clone lessons
                foreach ($module->lessons as $lesson) {
                    $clonedModule->lessons()->create([
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'content_type' => $lesson->content_type,
                        'content_path' => $lesson->content_path,
                        'content_url' => $lesson->content_url,
                        'duration' => $lesson->duration,
                        'order_index' => $lesson->order_index,
                        'is_downloadable' => $lesson->is_downloadable,
                        'metadata' => $lesson->metadata,
                    ]);
                }
            }

            DB::commit();

            Log::info('Course cloned', [
                'original_course_id' => $course->id,
                'cloned_course_id' => $clonedCourse->id,
                'creator_id' => $clonedData['created_by']
            ]);

            return $clonedCourse->fresh(['modules.lessons']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to clone course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate prerequisites
     */
    public function validatePrerequisites(array $prerequisiteIds, int $excludeCourseId = null): bool
    {
        if (empty($prerequisiteIds)) {
            return true;
        }

        // Check if all prerequisite courses exist
        $existingCourses = Course::whereIn('id', $prerequisiteIds)->pluck('id')->toArray();
        
        $missingCourses = array_diff($prerequisiteIds, $existingCourses);
        if (!empty($missingCourses)) {
            throw new \InvalidArgumentException(
                'Invalid prerequisite courses: ' . implode(', ', $missingCourses)
            );
        }

        // Check for circular dependencies
        if ($excludeCourseId) {
            $this->checkCircularDependencies($excludeCourseId, $prerequisiteIds);
        }

        return true;
    }

    /**
     * Check if user meets course prerequisites
     */
    public function userMeetsPrerequisites(Course $course, User $user): bool
    {
        if (!$course->hasPrerequisites()) {
            return true;
        }

        $prerequisites = $course->prerequisites;
        
        // Check if user has completed all prerequisite courses
        $completedCourses = $user->enrollments()
            ->whereIn('course_id', $prerequisites)
            ->where('status', 'completed')
            ->pluck('course_id')
            ->toArray();

        return count($completedCourses) === count($prerequisites);
    }

    /**
     * Get missing prerequisites for a user
     */
    public function getMissingPrerequisites(Course $course, User $user): array
    {
        if (!$course->hasPrerequisites()) {
            return [];
        }

        $prerequisites = $course->prerequisites;
        
        $completedCourses = $user->enrollments()
            ->whereIn('course_id', $prerequisites)
            ->where('status', 'completed')
            ->pluck('course_id')
            ->toArray();

        $missingIds = array_diff($prerequisites, $completedCourses);

        return Course::whereIn('id', $missingIds)->get()->toArray();
    }

    /**
     * Delete a course
     */
    public function deleteCourse(Course $course): bool
    {
        try {
            DB::beginTransaction();

            // Check if course has enrollments
            if ($course->enrollments()->exists()) {
                throw new \Exception('Cannot delete course with existing enrollments');
            }

            $courseId = $course->id;
            $courseTitle = $course->title;

            $this->courseRepository->delete($course);

            DB::commit();

            Log::info('Course deleted', [
                'course_id' => $courseId,
                'title' => $courseTitle
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Ensure slug is unique
     */
    protected function ensureUniqueSlug(string $slug, int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Course::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Validate course is ready for publishing
     */
    protected function validateCourseForPublishing(Course $course): void
    {
        $errors = [];

        // Check required fields
        if (empty($course->title)) {
            $errors[] = 'Course title is required';
        }

        if (empty($course->description)) {
            $errors[] = 'Course description is required';
        }

        if (empty($course->category_id)) {
            $errors[] = 'Course category is required';
        }

        // Check if course has at least one module
        if ($course->modules()->count() === 0) {
            $errors[] = 'Course must have at least one module';
        }

        // Check if course has at least one lesson
        $totalLessons = $course->modules()->withCount('lessons')->get()->sum('lessons_count');
        if ($totalLessons === 0) {
            $errors[] = 'Course must have at least one lesson';
        }

        if (!empty($errors)) {
            throw new \Exception('Course cannot be published: ' . implode(', ', $errors));
        }
    }

    /**
     * Check for circular dependencies in prerequisites
     */
    protected function checkCircularDependencies(int $courseId, array $prerequisiteIds, array $visited = []): void
    {
        if (in_array($courseId, $visited)) {
            throw new \InvalidArgumentException('Circular dependency detected in course prerequisites');
        }

        $visited[] = $courseId;

        foreach ($prerequisiteIds as $prereqId) {
            if ($prereqId == $courseId) {
                throw new \InvalidArgumentException('Course cannot be its own prerequisite');
            }

            $prereqCourse = Course::find($prereqId);
            if ($prereqCourse && $prereqCourse->hasPrerequisites()) {
                $this->checkCircularDependencies($courseId, $prereqCourse->prerequisites, $visited);
            }
        }
    }
}
