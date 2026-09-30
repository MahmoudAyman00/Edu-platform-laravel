<?php

namespace Tests\Feature;

use App\Events\Courses\CoursePublished;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Notifications\Courses\CoursePublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private function translations(string $title = 'Title'): array
    {
        return [
            ['locale' => 'ar', 'title' => $title.' عربي', 'description' => 'وصف'],
            ['locale' => 'en', 'title' => $title.' EN', 'description' => 'Description'],
        ];
    }

    private function category(): Category
    {
        $category = Category::create();
        $category->translations()->createMany($this->translations('Cat'));

        return $category;
    }

    private function coursePayload(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'price' => 100,
            'translations' => $this->translations('Laravel'),
        ];
    }

    public function test_admin_can_create_course_as_draft_and_publish_it(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        Event::fake([CoursePublished::class]);

        $created = $this->postJson('/api/courses/admin', $this->coursePayload($this->category()))
            ->assertCreated()->assertJsonPath('data.status', 'DRAFT');

        $id = $created->json('data.id');

        $this->postJson("/api/courses/admin/{$id}/publish")
            ->assertOk()->assertJsonPath('data.status', 'PUBLISHED');

        Event::assertDispatched(CoursePublished::class);
    }

    public function test_student_sees_only_published_courses(): void
    {
        Course::factory()->published()->create();
        Course::factory()->create(['status' => \App\Enums\CourseStatus::DRAFT]);

        Sanctum::actingAs(User::factory()->create());

        $list = $this->getJson('/api/courses')->assertOk();
        $this->assertCount(1, $list->json('data'));

        $draftId = Course::where('status', 'DRAFT')->first()->id;
        $this->getJson("/api/courses/{$draftId}")->assertNotFound();
    }

    public function test_admin_can_archive_course(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $course = Course::factory()->published()->create();

        $this->postJson("/api/courses/admin/{$course->id}/archive")
            ->assertOk()->assertJsonPath('data.status', 'ARCHIVED');
    }

    public function test_student_cannot_create_course(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/courses/admin', $this->coursePayload($this->category()))
            ->assertForbidden()->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_admin_list_route_is_not_shadowed_by_student_show(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        Course::factory()->published()->create();

        $this->getJson('/api/courses/admin')->assertOk()->assertJsonPath('success', true);
    }

    public function test_enrolled_student_keeps_access_to_archived_course(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        $course = Course::factory()->published()->create(['price' => 0]);
        $this->postJson('/api/enrollments/enroll', ['course_id' => $course->id])->assertCreated();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson("/api/courses/admin/{$course->id}/archive")->assertOk();

        Sanctum::actingAs($student);
        // Still open for the enrolled student...
        $this->getJson("/api/courses/{$course->id}")->assertOk();
        // ...but hidden from browse and still listed in mine.
        $this->getJson('/api/courses')->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/enrollments/mine')->assertOk()
            ->assertJsonPath('data.0.course_id', $course->id);
    }

    public function test_archived_course_rejects_new_enroll_and_strangers(): void
    {
        $course = Course::factory()->published()->create(['price' => 0]);
        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson("/api/courses/admin/{$course->id}/archive")->assertOk();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/enrollments/enroll', ['course_id' => $course->id])
            ->assertStatus(400)->assertJsonPath('code', 'COURSE_NOT_AVAILABLE');
        $this->getJson("/api/courses/{$course->id}")
            ->assertForbidden()->assertJsonPath('code', 'ENROLLMENT_REQUIRED');
    }

    public function test_lesson_hides_video_path_and_exposes_has_video(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $course = Course::factory()->published()->create();

        $this->postJson("/api/courses/admin/{$course->id}/lessons", [
            'video_path' => 'courses/1/lessons/a.mp4',
            'translations' => [
                ['locale' => 'ar', 'title' => 'درس'],
                ['locale' => 'en', 'title' => 'Lesson'],
            ],
        ])->assertCreated();

        $lesson = $this->getJson("/api/courses/admin/{$course->id}")->assertOk()
            ->json('data.lessons.0');

        $this->assertTrue($lesson['has_video']);
        $this->assertArrayNotHasKey('video_path', $lesson);
    }

    public function test_upload_url_rejects_non_video_files(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $course = Course::factory()->create();

        $this->postJson("/api/courses/admin/{$course->id}/lessons/upload-url", [
            'filename' => 'virus.exe',
        ])->assertStatus(422);
    }

    public function test_publish_notifies_students_only(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        Sanctum::actingAs($admin);
        $course = Course::factory()->create();
        $this->postJson("/api/courses/admin/{$course->id}/publish")->assertOk();

        Notification::assertSentTo($student, CoursePublishedNotification::class);
        Notification::assertNotSentTo($admin, CoursePublishedNotification::class);
    }

    public function test_admin_can_manage_lessons_reorder_and_upload_url(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        config(['content.video_disk' => 'local']);
        Storage::fake('local');

        $course = Course::factory()->create();
        $payload = fn (string $t) => ['translations' => $this->translations($t)];

        $first = $this->postJson("/api/courses/admin/{$course->id}/lessons", $payload('One'))
            ->assertCreated()->json('data.id');
        $second = $this->postJson("/api/courses/admin/{$course->id}/lessons", $payload('Two'))
            ->assertCreated()->json('data.id');

        $this->postJson("/api/courses/admin/{$course->id}/lessons/reorder", [
            'ordered_ids' => [$second, $first],
        ])->assertOk()->assertJsonPath('data.lessons.0.id', $second);

        $this->postJson("/api/courses/admin/{$course->id}/lessons/upload-url", [
            'filename' => 'intro.mp4',
        ])->assertOk()->assertJsonPath('data.path', fn ($path) => str_starts_with($path, "courses/{$course->id}/lessons/"));
    }

    public function test_reorder_with_foreign_lesson_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $course = Course::factory()->create();
        $other = Course::factory()->create();
        $foreign = $other->lessons()->create();
        $foreign->translations()->createMany($this->translations('X'));

        $this->postJson("/api/courses/admin/{$course->id}/lessons/reorder", [
            'ordered_ids' => [$foreign->id],
        ])->assertStatus(422)->assertJsonPath('code', 'INVALID_LESSON_ORDER');
    }

    public function test_status_filter_is_case_insensitive_and_validated(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        Course::factory()->published()->create();

        $this->getJson('/api/courses/admin?status=draft')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/courses/admin?status=PUBLISHED')->assertOk()->assertJsonPath('data.0.status', 'PUBLISHED');
        $this->getJson('/api/courses/admin?status=bogus')
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_STATUS');
    }
}
