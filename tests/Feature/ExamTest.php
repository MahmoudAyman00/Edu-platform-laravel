<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\ExamStatus;
use App\Events\Exams\ExamAttemptGraded;
use App\Models\Course;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    private function translations(string $title = 'Title'): array
    {
        return [
            ['locale' => 'ar', 'title' => $title.' عربي', 'description' => null],
            ['locale' => 'en', 'title' => $title.' EN', 'description' => null],
        ];
    }

    private function questionPayload(string $text = 'Q'): array
    {
        return [
            'points' => 10,
            'translations' => [
                ['locale' => 'ar', 'text' => $text.' عربي'],
                ['locale' => 'en', 'text' => $text.' EN'],
            ],
            'options' => [
                ['is_correct' => true, 'translations' => [['locale' => 'ar', 'text' => 'صح'], ['locale' => 'en', 'text' => 'Right']]],
                ['is_correct' => false, 'translations' => [['locale' => 'ar', 'text' => 'غلط'], ['locale' => 'en', 'text' => 'Wrong']]],
            ],
        ];
    }

    private function publishedExamWithQuestion(): Exam
    {
        $course = Course::factory()->published()->create(['price' => 0]);
        $exam = Exam::create([
            'course_id' => $course->id,
            'status' => ExamStatus::PUBLISHED,
            'pass_score' => 50,
            'max_attempts' => 2,
            'duration_minutes' => 30,
        ]);
        $exam->translations()->createMany($this->translations('Exam'));

        return $exam;
    }

    private function addQuestion(Exam $exam, ?User $as = null): \App\Models\Question
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->postJson("/api/exams/admin/{$exam->id}/questions", $this->questionPayload());

        if ($as) {
            Sanctum::actingAs($as);
        }

        return \App\Models\Question::findOrFail($response->json('data.id'));
    }

    public function test_admin_list_route_is_not_shadowed_by_student_show(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/exams/admin')->assertOk()->assertJsonPath('success', true);
    }

    public function test_status_filter_is_case_insensitive_and_validated(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/exams/admin?status=draft')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/exams/admin?status=bogus')
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_STATUS');
    }

    public function test_admin_can_create_publish_and_set_final_exam(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $course = Course::factory()->create();

        $created = $this->postJson('/api/exams/admin', [
            'course_id' => $course->id,
            'pass_score' => 60,
            'max_attempts' => 3,
            'translations' => $this->translations('Final'),
        ])->assertCreated()->assertJsonPath('data.status', 'DRAFT');

        $id = $created->json('data.id');

        $this->postJson("/api/exams/admin/{$id}/publish")->assertOk()
            ->assertJsonPath('data.status', 'PUBLISHED');

        $this->postJson("/api/exams/admin/{$id}/final", ['is_final' => true])->assertOk()
            ->assertJsonPath('data.is_final', true);

        // Only one final per course: setting another unsets this one.
        $other = Exam::create(['course_id' => $course->id, 'status' => ExamStatus::DRAFT]);
        $other->translations()->createMany($this->translations('Other'));
        $this->postJson("/api/exams/admin/{$other->id}/final", ['is_final' => true])->assertOk();

        $this->assertFalse(Exam::find($id)->is_final);
        $this->assertTrue($other->fresh()->is_final);
    }

    public function test_student_cannot_see_draft_exam(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $course = Course::factory()->published()->create(['price' => 0]);
        $exam = Exam::create(['course_id' => $course->id, 'status' => ExamStatus::DRAFT]);
        $exam->translations()->createMany($this->translations('Draft'));

        $this->getJson("/api/exams/{$exam->id}")->assertNotFound();
        $this->getJson("/api/exams/course/{$course->id}")->assertForbidden();
    }

    public function test_start_returns_ongoing_attempt_and_submit_grades(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        Event::fake([ExamAttemptGraded::class]);
        $exam = $this->publishedExamWithQuestion();
        $question = $this->addQuestion($exam, $student);
        $correctId = $question->options()->where('is_correct', true)->first()->id;
        $wrongId = $question->options()->where('is_correct', false)->first()->id;

        $this->postJson('/api/enrollments/enroll', ['course_id' => $exam->course_id])->assertCreated();

        $first = $this->postJson("/api/exams/{$exam->id}/start")->assertCreated()->json('data.id');
        $second = $this->postJson("/api/exams/{$exam->id}/start")->assertCreated()->json('data.id');
        $this->assertEquals($first, $second);

        // Correct answer -> 100, passed.
        $this->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [['question_id' => $question->id, 'selected_option_id' => $correctId]],
        ])->assertOk()
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.status', 'SUBMITTED');

        Event::assertDispatched(ExamAttemptGraded::class);

        // Second attempt with wrong answer -> 0, failed.
        $this->postJson("/api/exams/{$exam->id}/start")->assertCreated();
        $this->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [['question_id' => $question->id, 'selected_option_id' => $wrongId]],
        ])->assertOk()
            ->assertJsonPath('data.score', 0)
            ->assertJsonPath('data.passed', false);
    }

    public function test_max_attempts_is_enforced(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        $exam = $this->publishedExamWithQuestion(); // max_attempts = 2
        $question = $this->addQuestion($exam, $student);

        $this->postJson('/api/enrollments/enroll', ['course_id' => $exam->course_id])->assertCreated();

        for ($i = 0; $i < 2; $i++) {
            $this->postJson("/api/exams/{$exam->id}/start")->assertCreated();
            $this->postJson("/api/exams/{$exam->id}/submit", [
                'answers' => [['question_id' => $question->id, 'selected_option_id' => null]],
            ])->assertOk();
        }

        $this->postJson("/api/exams/{$exam->id}/start")
            ->assertForbidden()->assertJsonPath('code', 'MAX_ATTEMPTS_REACHED');
    }

    public function test_result_returns_latest_graded_attempt(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        $exam = $this->publishedExamWithQuestion();
        $question = $this->addQuestion($exam, $student);
        $correctId = $question->options()->where('is_correct', true)->first()->id;

        $this->postJson('/api/enrollments/enroll', ['course_id' => $exam->course_id])->assertCreated();

        $this->getJson("/api/exams/{$exam->id}/result")->assertNotFound();

        $this->postJson("/api/exams/{$exam->id}/start")->assertCreated();
        $this->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [['question_id' => $question->id, 'selected_option_id' => $correctId]],
        ])->assertOk();

        $this->getJson("/api/exams/{$exam->id}/result")
            ->assertOk()->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.answers.0.is_correct', true);
    }

    public function test_question_without_correct_option_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $exam = $this->publishedExamWithQuestion();
        $payload = $this->questionPayload();
        $payload['options'][0]['is_correct'] = false;

        $this->postJson("/api/exams/admin/{$exam->id}/questions", $payload)
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_OPTIONS');
    }

    public function test_two_correct_options_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $exam = $this->publishedExamWithQuestion();
        $payload = $this->questionPayload();
        $payload['options'][1]['is_correct'] = true;

        $this->postJson("/api/exams/admin/{$exam->id}/questions", $payload)
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_OPTIONS');
    }

    public function test_admin_can_reorder_questions(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $exam = $this->publishedExamWithQuestion();

        $first = $this->postJson("/api/exams/admin/{$exam->id}/questions", $this->questionPayload('Q1'))
            ->assertCreated()->json('data.id');
        $second = $this->postJson("/api/exams/admin/{$exam->id}/questions", $this->questionPayload('Q2'))
            ->assertCreated()->json('data.id');

        $this->postJson("/api/exams/admin/{$exam->id}/questions/reorder", [
            'ordered_ids' => [$second, $first],
        ])->assertOk()->assertJsonPath('data.questions.0.id', $second);

        $this->postJson("/api/exams/admin/{$exam->id}/questions/reorder", [
            'ordered_ids' => [$first, 999999],
        ])->assertStatus(422)->assertJsonPath('code', 'INVALID_QUESTION_ORDER');
    }

    public function test_student_exam_view_hides_correct_answers(): void
    {
        Sanctum::actingAs($student = User::factory()->create());
        $exam = $this->publishedExamWithQuestion();
        $this->addQuestion($exam, $student);

        $this->postJson('/api/enrollments/enroll', ['course_id' => $exam->course_id])->assertCreated();

        $view = $this->getJson("/api/exams/{$exam->id}")->assertOk();
        $this->assertArrayNotHasKey('is_correct', $view->json('data.exam.questions.0.options.0'));
        $this->assertEquals(2, $view->json('data.attempts_left'));
    }
}
