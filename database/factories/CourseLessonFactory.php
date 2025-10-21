<?php

namespace Database\Factories;

use App\Models\CourseLesson;
use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseLessonFactory extends Factory
{
    protected $model = CourseLesson::class;

    public function definition(): array
    {
        $contentType = $this->faker->randomElement(['video', 'pdf', 'text', 'scorm']);
        
        return [
            'module_id' => CourseModule::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'content_type' => $contentType,
            'content_path' => $contentType === 'text' ? null : 'courses/' . $contentType . 's/sample.' . $contentType,
            'content_url' => null,
            'duration' => $this->faker->numberBetween(5, 60),
            'order_index' => $this->faker->numberBetween(0, 10),
            'is_downloadable' => $this->faker->boolean(),
            'metadata' => [],
        ];
    }

    public function forModule(CourseModule $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module_id' => $module->id,
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'video',
            'content_path' => 'courses/videos/sample.mp4',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'pdf',
            'content_path' => 'courses/pdfs/sample.pdf',
        ]);
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'text',
            'content_path' => null,
        ]);
    }

    public function downloadable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_downloadable' => true,
        ]);
    }
}
