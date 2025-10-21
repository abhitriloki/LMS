<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuleService
{
    /**
     * Create a new module for a course
     */
    public function createModule(Course $course, array $data): CourseModule
    {
        try {
            DB::beginTransaction();

            // Set course_id
            $data['course_id'] = $course->id;

            // Set order_index if not provided
            if (!isset($data['order_index'])) {
                $data['order_index'] = $course->modules()->max('order_index') + 1;
            }

            $module = CourseModule::create($data);

            DB::commit();

            Log::info('Module created', [
                'module_id' => $module->id,
                'course_id' => $course->id,
                'title' => $module->title
            ]);

            return $module;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create module', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing module
     */
    public function updateModule(CourseModule $module, array $data): CourseModule
    {
        try {
            DB::beginTransaction();

            $module->update($data);

            DB::commit();

            Log::info('Module updated', [
                'module_id' => $module->id,
                'title' => $module->title
            ]);

            return $module->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update module', [
                'module_id' => $module->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a module
     */
    public function deleteModule(CourseModule $module): bool
    {
        try {
            DB::beginTransaction();

            $moduleId = $module->id;
            $courseId = $module->course_id;

            // Delete all lessons in the module
            $module->lessons()->delete();

            // Delete the module
            $module->delete();

            // Reorder remaining modules
            $this->reorderModulesAfterDeletion($courseId, $module->order_index);

            DB::commit();

            Log::info('Module deleted', [
                'module_id' => $moduleId,
                'course_id' => $courseId
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete module', [
                'module_id' => $module->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reorder modules using drag-and-drop order
     */
    public function reorderModules(Course $course, array $moduleOrder): void
    {
        try {
            DB::beginTransaction();

            // $moduleOrder should be an array of module IDs in the desired order
            foreach ($moduleOrder as $index => $moduleId) {
                CourseModule::where('id', $moduleId)
                    ->where('course_id', $course->id)
                    ->update(['order_index' => $index]);
            }

            DB::commit();

            Log::info('Modules reordered', [
                'course_id' => $course->id,
                'module_count' => count($moduleOrder)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder modules', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Duplicate a module within the same course
     */
    public function duplicateModule(CourseModule $module): CourseModule
    {
        try {
            DB::beginTransaction();

            // Create duplicate module
            $duplicateData = $module->toArray();
            unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at']);
            
            $duplicateData['title'] = $duplicateData['title'] . ' (Copy)';
            $duplicateData['order_index'] = $module->course->modules()->max('order_index') + 1;

            $duplicateModule = CourseModule::create($duplicateData);

            // Duplicate lessons
            foreach ($module->lessons as $lesson) {
                $lessonData = $lesson->toArray();
                unset($lessonData['id'], $lessonData['created_at'], $lessonData['updated_at']);
                $lessonData['module_id'] = $duplicateModule->id;

                $duplicateModule->lessons()->create($lessonData);
            }

            DB::commit();

            Log::info('Module duplicated', [
                'original_module_id' => $module->id,
                'duplicate_module_id' => $duplicateModule->id
            ]);

            return $duplicateModule->fresh(['lessons']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate module', [
                'module_id' => $module->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reorder modules after deletion
     */
    protected function reorderModulesAfterDeletion(int $courseId, int $deletedOrderIndex): void
    {
        CourseModule::where('course_id', $courseId)
            ->where('order_index', '>', $deletedOrderIndex)
            ->decrement('order_index');
    }
}
