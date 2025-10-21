<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
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
            'course_id' => $this->course_id,
            'user_id' => $this->user_id,
            'enrollment_type' => $this->enrollment_type,
            'status' => $this->status,
            'progress_percentage' => (float) $this->progress_percentage,
            'final_score' => $this->final_score ? (float) $this->final_score : null,
            'deadline' => $this->deadline?->toIso8601String(),
            'completion_date' => $this->completion_date?->toIso8601String(),
            'last_accessed_at' => $this->last_accessed_at?->toIso8601String(),
            'course' => new CourseResource($this->whenLoaded('course')),
            'user' => new UserResource($this->whenLoaded('user')),
            'certificate' => new CertificateResource($this->whenLoaded('certificate')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
