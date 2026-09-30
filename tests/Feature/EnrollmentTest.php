<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Events\Enrollments\StudentEnrolled;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function translations(string $title = 'Title'): array
    {
        return [
            ['locale' => 'ar', 'title' => $title.' عربي', 'description' => null],
            ['locale' => 'en', 'title' => $title.' EN', 'description' => null],
        ];
    }

    private function freeCourse(): Course
    {
        return Course::factory()->published()->create(['price' => 0]);
    }

    public function test_student_can_enroll_free_and_event_is_dispatched(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        Event::fake([StudentEnrolled::class]);

        $this->postJson('/api/enrollments/enroll', ['course_id' => $this->freeCourse()->id])
            ->assertCreated()->assertJsonPath('success', true);

        Event::assertDispatched(StudentEnrolled::class);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'source' => 'FREE',
        ]);
    }

    public function test_duplicate_enroll_is_idempotent(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Event::fake([StudentEnrolled::class]);
        $courseId = $this->freeCourse()->id;

        $this->postJson('/api/enrollments/enroll', ['course_id' => $courseId])->assertCreated();
        $this->postJson('/api/enrollments/enroll', ['course_id' => $courseId])->assertCreated();

        $this->assertDatabaseCount('enrollments', 1);
        Event::assertDispatched(StudentEnrolled::class, 1);
    }

    public function test_cannot_enroll_in_paid_course_for_free(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $course = Course::factory()->published()->create(['price' => 100]);

        $this->postJson('/api/enrollments/enroll', ['course_id' => $course->id])
            ->assertStatus(400)->assertJsonPath('code', 'COURSE_NOT_FREE');
    }

    public function test_cannot_enroll_in_draft_course(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $course = Course::factory()->create(['status' => CourseStatus::DRAFT, 'price' => 0]);

        $this->postJson('/api/enrollments/enroll', ['course_id' => $course->id])
            ->assertStatus(400)->assertJsonPath('code', 'COURSE_NOT_AVAILABLE');
    }

    public function test_guest_cannot_enroll(): void
    {
        $this->postJson('/api/enrollments/enroll', ['course_id' => 1])->assertUnauthorized();
    }

    public function test_enrolled_student_can_get_playback_url(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        $course = $this->freeCourse();
        $lesson = $course->lessons()->create(['video_path' => 'courses/1/lessons/a.mp4']);
        $lesson->translations()->createMany($this->translations('L'));

        $this->postJson('/api/enrollments/enroll', ['course_id' => $course->id])->assertCreated();

        $this->getJson("/api/enrollments/lessons/{$lesson->id}/playback")
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('data.play_url', fn ($url) => str_contains($url, 'courses/1/lessons/a.mp4'));
    }

    public function test_playback_without_enrollment_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $course = $this->freeCourse();
        $lesson = $course->lessons()->create(['video_path' => 'x.mp4']);
        $lesson->translations()->createMany($this->translations('L'));

        $this->getJson("/api/enrollments/lessons/{$lesson->id}/playback")
            ->assertForbidden()->assertJsonPath('code', 'ENROLLMENT_REQUIRED');
    }

    public function test_admin_can_play_without_enrollment(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $course = $this->freeCourse();
        $lesson = $course->lessons()->create(['video_path' => 'x.mp4']);
        $lesson->translations()->createMany($this->translations('L'));

        $this->getJson("/api/enrollments/lessons/{$lesson->id}/playback")->assertOk();
    }
}
