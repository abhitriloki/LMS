<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
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
            'user_id' => $this->user_id,
            'course_id' => $this->course_id,
            'certificate_number' => $this->certificate_number,
            'issued_at' => $this->issued_at->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'pdf_path' => $this->pdf_path ? asset('storage/' . $this->pdf_path) : null,
            'qr_code' => $this->qr_code,
            'user' => new UserResource($this->whenLoaded('user')),
            'course' => new CourseResource($this->whenLoaded('course')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
