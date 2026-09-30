<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::create()->getKey(),
            'price' => 0,
            'status' => CourseStatus::DRAFT,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Course $course) {
            if ($course->category->translations()->count() === 0) {
                $course->category->translations()->createMany([
                    ['locale' => 'ar', 'title' => 'تصنيف '.$course->category->getKey(), 'description' => null],
                    ['locale' => 'en', 'title' => 'Category '.$course->category->getKey(), 'description' => null],
                ]);
            }

            $course->translations()->createMany([
                ['locale' => 'ar', 'title' => 'كورس '.$course->getKey(), 'description' => null],
                ['locale' => 'en', 'title' => 'Course '.$course->getKey(), 'description' => null],
            ]);
        });
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::PUBLISHED,
        ]);
    }
}
