<?php

namespace App\Services;

use App\Models\CourseCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class CategoryService
{
    /**
     * Create a new category
     */
    public function createCategory(array $data): CourseCategory
    {
        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure unique slug
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        // Set order_index if not provided
        if (!isset($data['order_index'])) {
            $data['order_index'] = $this->getNextOrderIndex($data['parent_id'] ?? null);
        }

        return CourseCategory::create($data);
    }

    /**
     * Update an existing category
     */
    public function updateCategory(CourseCategory $category, array $data): CourseCategory
    {
        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $category->id);
        }

        // Prevent circular parent relationship
        if (isset($data['parent_id']) && $data['parent_id']) {
            if ($this->wouldCreateCircularReference($category->id, $data['parent_id'])) {
                throw new \InvalidArgumentException('Cannot set parent: would create circular reference');
            }
        }

        $category->update($data);
        return $category->fresh();
    }

    /**
     * Delete a category
     */
    public function deleteCategory(CourseCategory $category): bool
    {
        // Check if category has courses
        if ($category->courses()->exists()) {
            throw new \RuntimeException('Cannot delete category with associated courses');
        }

        // Move children to parent or make them root categories
        if ($category->hasChildren()) {
            $category->children()->update(['parent_id' => $category->parent_id]);
        }

        return $category->delete();
    }

    /**
     * Get all categories in hierarchical tree structure
     */
    public function getCategoryTree(): Collection
    {
        return CourseCategory::whereNull('parent_id')
            ->with('descendants')
            ->orderBy('order_index')
            ->get();
    }

    /**
     * Get all categories as flat list
     */
    public function getAllCategories(): Collection
    {
        return CourseCategory::with('parent')
            ->orderBy('order_index')
            ->get();
    }

    /**
     * Reorder categories
     */
    public function reorderCategories(array $order): void
    {
        foreach ($order as $index => $categoryId) {
            CourseCategory::where('id', $categoryId)
                ->update(['order_index' => $index]);
        }
    }

    /**
     * Get category with all its data
     */
    public function getCategoryWithRelations(int $id): CourseCategory
    {
        return CourseCategory::with(['parent', 'children', 'courses'])
            ->findOrFail($id);
    }

    /**
     * Ensure slug is unique
     */
    protected function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     */
    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = CourseCategory::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get next order index for a parent
     */
    protected function getNextOrderIndex(?int $parentId): int
    {
        $query = CourseCategory::query();

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->max('order_index') + 1;
    }

    /**
     * Check if setting parent would create circular reference
     */
    protected function wouldCreateCircularReference(int $categoryId, int $parentId): bool
    {
        if ($categoryId === $parentId) {
            return true;
        }

        $parent = CourseCategory::find($parentId);
        
        while ($parent) {
            if ($parent->id === $categoryId) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }
}
