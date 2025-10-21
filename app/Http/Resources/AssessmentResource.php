<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'passing_score' => (float) $this->passing_score,
            'time_limit' => $this->time_limit,
            'max_attempts' => $this->max_attempts,
            'randomize_questions' => $this->randomize_questions,
            'randomize_options' => $this->randomize_options,
            'show_results' => $this->show_results,
            'show_correct_answers' => $this->show_correct_answers,
            'allow_review' => $this->allow_review,
            'is_published' => $this->is_published,
            'course' => new CourseResource($this->whenLoaded('course')),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'questions_count' => $this->when(isset($this->questions_count), $this->questions_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
