<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'difficulty_level' => $this->difficulty_level,
            'estimated_duration' => $this->estimated_duration,
            'thumbnail' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'preview_video' => $this->preview_video ? asset('storage/' . $this->preview_video) : null,
            'price' => $this->price,
            'is_mandatory' => $this->is_mandatory,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'prerequisites' => $this->prerequisites,
            'learning_objectives' => $this->learning_objectives,
            'target_audience' => $this->target_audience,
            'language' => $this->language,
            'tags' => $this->tags,
            'category' => new CourseCategoryResource($this->whenLoaded('category')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'modules' => ModuleResource::collection($this->whenLoaded('modules')),
            'enrollments_count' => $this->when(isset($this->enrollments_count), $this->enrollments_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
