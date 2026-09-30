<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    private function translations(string $title = 'Title'): array
    {
        return [
            ['locale' => 'ar', 'title' => $title.' عربي', 'description' => null],
            ['locale' => 'en', 'title' => $title.' EN', 'description' => null],
        ];
    }

    private function courseWithLessons(int $count = 2): Course
    {
        $course = Course::factory()->published()->create(['price' => 0]);

        for ($i = 1; $i <= $count; $i++) {
            $lesson = $course->lessons()->create(['sort' => $i]);
            $lesson->translations()->createMany($this->translations("L{$i}"));
        }

        return $course;
    }

    public function test_student_can_complete_lesson_and_see_percent(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $course = $this->courseWithLessons(2);
        $lessonId = $course->lessons()->first()->id;

        $this->postJson('/api/enrollments/enroll', ['course_id' => $course->id])->assertCreated();

        $this->postJson("/api/progress/lessons/{$lessonId}/complete")
            ->assertOk()
            ->assertJsonPath('data.percent', 50)
            ->assertJsonPath('data.completed_lessons', 1)
            ->assertJsonPath('data.completed_lesson_ids', [$lessonId])
            ->assertJsonPath('data.total_lessons', 2);

        $this->getJson("/api/progress/courses/{$course->id}")
            ->assertOk()->assertJsonPath('data.percent', 50);
    }

    public function test_complete_without_enrollment_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $course = $this->courseWithLessons(1);

        $this->postJson("/api/progress/lessons/{$course->lessons()->first()->id}/complete")
            ->assertForbidden()->assertJsonPath('code', 'ENROLLMENT_REQUIRED');
    }

    public function test_my_courses_lists_progress(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $course = $this->courseWithLessons(1);

        $this->postJson('/api/enrollments/enroll', ['course_id' => $course->id])->assertCreated();
        $this->postJson("/api/progress/lessons/{$course->lessons()->first()->id}/complete")->assertOk();

        $this->getJson('/api/progress/my-courses')
            ->assertOk()
            ->assertJsonPath('data.0.percent', 100)
            ->assertJsonPath('data.0.course.id', $course->id);
    }

    public function test_completing_twice_keeps_single_row(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $course = $this->courseWithLessons(1);
        $lessonId = $course->lessons()->first()->id;

        $this->postJson('/api/enrollments/enroll', ['course_id' => $course->id])->assertCreated();
        $this->postJson("/api/progress/lessons/{$lessonId}/complete")->assertOk();
        $this->postJson("/api/progress/lessons/{$lessonId}/complete")->assertOk();

        $this->assertDatabaseCount('lesson_progress', 1);
    }
}
