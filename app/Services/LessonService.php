<?php

namespace App\Services;

use App\Models\CourseModule;
use App\Models\CourseLesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class LessonService
{
    /**
     * Create a new lesson for a module
     */
    public function createLesson(CourseModule $module, array $data): CourseLesson
    {
        try {
            DB::beginTransaction();

            // Set module_id
            $data['module_id'] = $module->id;

            // Set order_index if not provided
            if (!isset($data['order_index'])) {
                $data['order_index'] = $module->lessons()->max('order_index') + 1;
            }

            // Set defaults
            $data['is_downloadable'] = $data['is_downloadable'] ?? false;

            $lesson = CourseLesson::create($data);

            DB::commit();

            Log::info('Lesson created', [
                'lesson_id' => $lesson->id,
                'module_id' => $module->id,
                'title' => $lesson->title
            ]);

            return $lesson;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create lesson', [
                'module_id' => $module->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing lesson
     */
    public function updateLesson(CourseLesson $lesson, array $data): CourseLesson
    {
        try {
            DB::beginTransaction();

            $lesson->update($data);

            DB::commit();

            Log::info('Lesson updated', [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title
            ]);

            return $lesson->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update lesson', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a lesson
     */
    public function deleteLesson(CourseLesson $lesson): bool
    {
        try {
            DB::beginTransaction();

            $lessonId = $lesson->id;
            $moduleId = $lesson->module_id;
            $orderIndex = $lesson->order_index;

            // Delete associated content file if exists
            if ($lesson->content_path) {
                $this->deleteContentFile($lesson->content_path);
            }

            // Delete the lesson
            $lesson->delete();

            // Reorder remaining lessons
            $this->reorderLessonsAfterDeletion($moduleId, $orderIndex);

            DB::commit();

            Log::info('Lesson deleted', [
                'lesson_id' => $lessonId,
                'module_id' => $moduleId
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete lesson', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reorder lessons using drag-and-drop order
     */
    public function reorderLessons(CourseModule $module, array $lessonOrder): void
    {
        try {
            DB::beginTransaction();

            // $lessonOrder should be an array of lesson IDs in the desired order
            foreach ($lessonOrder as $index => $lessonId) {
                CourseLesson::where('id', $lessonId)
                    ->where('module_id', $module->id)
                    ->update(['order_index' => $index]);
            }

            DB::commit();

            Log::info('Lessons reordered', [
                'module_id' => $module->id,
                'lesson_count' => count($lessonOrder)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder lessons', [
                'module_id' => $module->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle content upload for a lesson
     */
    public function uploadContent(CourseLesson $lesson, UploadedFile $file, string $contentType): CourseLesson
    {
        try {
            DB::beginTransaction();

            // Delete old content file if exists
            if ($lesson->content_path) {
                $this->deleteContentFile($lesson->content_path);
            }

            // Determine storage path based on content type
            $storagePath = $this->getStoragePath($contentType);

            // Store the file
            $path = $file->store($storagePath, 'public');

            // Update lesson with new content path
            $lesson->update([
                'content_path' => $path,
                'content_type' => $contentType,
            ]);

            DB::commit();

            Log::info('Content uploaded for lesson', [
                'lesson_id' => $lesson->id,
                'content_type' => $contentType,
                'path' => $path
            ]);

            return $lesson->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to upload content', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Duplicate a lesson within the same module
     */
    public function duplicateLesson(CourseLesson $lesson): CourseLesson
    {
        try {
            DB::beginTransaction();

            $duplicateData = $lesson->toArray();
            unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at']);
            
            $duplicateData['title'] = $duplicateData['title'] . ' (Copy)';
            $duplicateData['order_index'] = $lesson->module->lessons()->max('order_index') + 1;

            $duplicateLesson = CourseLesson::create($duplicateData);

            DB::commit();

            Log::info('Lesson duplicated', [
                'original_lesson_id' => $lesson->id,
                'duplicate_lesson_id' => $duplicateLesson->id
            ]);

            return $duplicateLesson;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate lesson', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Move lesson to another module
     */
    public function moveLesson(CourseLesson $lesson, CourseModule $targetModule): CourseLesson
    {
        try {
            DB::beginTransaction();

            $oldModuleId = $lesson->module_id;
            $oldOrderIndex = $lesson->order_index;

            // Update lesson's module
            $lesson->update([
                'module_id' => $targetModule->id,
                'order_index' => $targetModule->lessons()->max('order_index') + 1
            ]);

            // Reorder lessons in old module
            $this->reorderLessonsAfterDeletion($oldModuleId, $oldOrderIndex);

            DB::commit();

            Log::info('Lesson moved to another module', [
                'lesson_id' => $lesson->id,
                'old_module_id' => $oldModuleId,
                'new_module_id' => $targetModule->id
            ]);

            return $lesson->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to move lesson', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get storage path based on content type
     */
    protected function getStoragePath(string $contentType): string
    {
        return match($contentType) {
            'video' => 'courses/videos',
            'pdf' => 'courses/pdfs',
            'scorm' => 'courses/scorm',
            'presentation' => 'courses/presentations',
            default => 'courses/content',
        };
    }

    /**
     * Delete content file from storage
     */
    protected function deleteContentFile(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Reorder lessons after deletion
     */
    protected function reorderLessonsAfterDeletion(int $moduleId, int $deletedOrderIndex): void
    {
        CourseLesson::where('module_id', $moduleId)
            ->where('order_index', '>', $deletedOrderIndex)
            ->decrement('order_index');
    }
}
