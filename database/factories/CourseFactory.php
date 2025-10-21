<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);
        
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraphs(3, true),
            'short_description' => $this->faker->sentence(),
            'category_id' => CourseCategory::factory(),
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'estimated_duration' => $this->faker->numberBetween(60, 600),
            'thumbnail' => null,
            'preview_video' => null,
            'price' => $this->faker->randomFloat(2, 0, 999),
            'is_mandatory' => false,
            'is_published' => false,
            'is_featured' => false,
            'prerequisites' => [],
            'learning_objectives' => [
                $this->faker->sentence(),
                $this->faker->sentence(),
                $this->faker->sentence(),
            ],
            'target_audience' => $this->faker->sentence(),
            'language' => 'en',
            'tags' => [$this->faker->word(), $this->faker->word()],
            'metadata' => [],
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function mandatory(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_mandatory' => true,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function withPrerequisites(array $prerequisiteIds): static
    {
        return $this->state(fn (array $attributes) => [
            'prerequisites' => $prerequisiteIds,
        ]);
    }

    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'beginner',
        ]);
    }

    public function intermediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'intermediate',
        ]);
    }

    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'advanced',
        ]);
    }
}
