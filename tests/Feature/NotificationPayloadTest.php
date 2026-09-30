<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\ExamStatus;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Notifications\Courses\CoursePublishedNotification;
use App\Notifications\Enrollments\EnrolledInCourseNotification;
use App\Notifications\Exams\ExamResultNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_payload_has_translated_title_body_in_user_locale(): void
    {
        $course = Course::factory()->published()->create();
        $arUser = User::factory()->create(['locale' => 'ar']);
        $enUser = User::factory()->create(['locale' => 'en']);

        $ar = (new CoursePublishedNotification($course, $arUser->id))->toBroadcast($arUser)->data;
        $en = (new CoursePublishedNotification($course, $enUser->id))->toBroadcast($enUser)->data;

        $this->assertStringStartsWith('كورس جديد:', $ar['title']);
        $this->assertStringStartsWith('New course:', $en['title']);
        $this->assertNotEmpty($ar['body']);
        $this->assertNotEmpty($en['body']);

        // Key + params stay identical to the database payload.
        $this->assertSame('course_published', $ar['key']);
        $this->assertSame($ar['params'], $en['params']);
        $this->assertArrayHasKey('course_id', $ar);
    }

    public function test_database_payload_has_no_title_body(): void
    {
        $course = Course::factory()->published()->create();
        $user = User::factory()->create();

        $data = (new CoursePublishedNotification($course, $user->id))->toArray($user);

        $this->assertArrayNotHasKey('title', $data);
        $this->assertArrayNotHasKey('body', $data);
        $this->assertSame('course_published', $data['key']);
    }

    public function test_enrolled_broadcast_payload_is_translated(): void
    {
        $course = Course::factory()->published()->create();
        $enUser = User::factory()->create(['locale' => 'en']);

        $payload = (new EnrolledInCourseNotification($course, $enUser->id))->toBroadcast($enUser)->data;

        $this->assertSame('enrolled', $payload['key']);
        $this->assertStringStartsWith('Enrolled in', $payload['title']);
        $this->assertArrayHasKey('course_id', $payload);
    }

    public function test_exam_result_payload_has_score_status_pass_score(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);
        $course = Course::factory()->published()->create(['price' => 0]);
        $exam = Exam::create([
            'course_id' => $course->id,
            'status' => ExamStatus::PUBLISHED,
            'pass_score' => 70,
            'max_attempts' => 3,
        ]);
        $exam->translations()->create(['locale' => 'ar', 'title' => 'امتحان']);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'status' => AttemptStatus::SUBMITTED,
            'score' => 80,
            'passed' => true,
        ]);

        $db = (new ExamResultNotification($attempt, $user->id))->toArray($user);
        $this->assertSame(80, $db['params']['score']);
        $this->assertSame('passed', $db['params']['status']);
        $this->assertSame(70, $db['params']['pass_score']);
        $this->assertSame($exam->id, $db['exam_id']);
        $this->assertSame($attempt->id, $db['attempt_id']);

        $broadcast = (new ExamResultNotification($attempt, $user->id))->toBroadcast($user)->data;
        $this->assertSame('نتيجة الامتحان', $broadcast['title']);
        $this->assertSame(70, $broadcast['params']['pass_score']);
    }
}
