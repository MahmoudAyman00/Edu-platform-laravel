<?php

namespace Tests\Feature;

use App\Enums\ExamStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['content.video_disk' => 'local']);
        Storage::fake('local');
    }

    private function translations(string $title = 'Title'): array
    {
        return [
            ['locale' => 'ar', 'title' => $title.' عربي', 'description' => null],
            ['locale' => 'en', 'title' => $title.' EN', 'description' => null],
        ];
    }

    private function finalExam(): Exam
    {
        $course = Course::factory()->published()->create(['price' => 0]);
        $exam = Exam::create([
            'course_id' => $course->id,
            'status' => ExamStatus::PUBLISHED,
            'is_final' => true,
            'pass_score' => 50,
            'max_attempts' => 5,
        ]);
        $exam->translations()->createMany($this->translations('Final'));

        return $exam;
    }

    private function addQuestion(Exam $exam): Question
    {
        $question = $exam->questions()->create(['points' => 10, 'sort' => 1]);
        $question->translations()->createMany([
            ['locale' => 'ar', 'text' => 'س؟'],
            ['locale' => 'en', 'text' => 'Q?'],
        ]);
        foreach ([[true, 'صح'], [false, 'غلط']] as [$correct, $text]) {
            $option = $question->options()->create(['is_correct' => $correct]);
            $option->translations()->create(['locale' => 'ar', 'text' => $text]);
        }

        return $question;
    }

    private function passExam(User $student, Exam $exam, Question $question): void
    {
        Sanctum::actingAs($student);
        $this->postJson('/api/enrollments/enroll', ['course_id' => $exam->course_id])->assertCreated();
        $this->postJson("/api/exams/{$exam->id}/start")->assertCreated();
        $correct = $question->options()->where('is_correct', true)->first()->id;
        $this->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [['question_id' => $question->id, 'selected_option_id' => $correct]],
        ])->assertOk()->assertJsonPath('data.passed', true);
    }

    public function test_passing_final_exam_issues_certificate_with_pdf(): void
    {
        $student = User::factory()->create();
        $exam = $this->finalExam();
        $question = $this->addQuestion($exam);

        $this->passExam($student, $exam, $question);

        $cert = Certificate::where('user_id', $student->id)->firstOrFail();
        $this->assertNotEmpty($cert->serial_number);
        $this->assertNotNull($cert->pdf_path); // sync queue runs the job inline
        Storage::disk('local')->assertExists($cert->pdf_path);

        Sanctum::actingAs($student);
        $this->getJson('/api/certificates/mine')->assertOk()
            ->assertJsonPath('data.0.serial_number', $cert->serial_number);
    }

    public function test_certificate_is_idempotent(): void
    {
        $student = User::factory()->create();
        $exam = $this->finalExam();
        $question = $this->addQuestion($exam);

        $this->passExam($student, $exam, $question);
        $this->passExam($student, $exam, $question);

        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_non_final_pass_and_final_fail_issue_nothing(): void
    {
        $student = User::factory()->create();

        // Non-final pass.
        $course = Course::factory()->published()->create(['price' => 0]);
        $quiz = Exam::create([
            'course_id' => $course->id, 'status' => ExamStatus::PUBLISHED,
            'is_final' => false, 'pass_score' => 50, 'max_attempts' => 5,
        ]);
        $quiz->translations()->createMany($this->translations('Quiz'));
        $this->passExam($student, $quiz, $this->addQuestion($quiz));

        // Final fail.
        $exam = $this->finalExam();
        $question = $this->addQuestion($exam);
        Sanctum::actingAs($student);
        $this->postJson('/api/enrollments/enroll', ['course_id' => $exam->course_id])->assertCreated();
        $this->postJson("/api/exams/{$exam->id}/start")->assertCreated();
        $wrong = $question->options()->where('is_correct', false)->first()->id;
        $this->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [['question_id' => $question->id, 'selected_option_id' => $wrong]],
        ])->assertOk()->assertJsonPath('data.passed', false);

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_download_not_ready_returns_409(): void
    {
        $student = User::factory()->create();
        Sanctum::actingAs($student);
        $course = Course::factory()->published()->create(['price' => 0]);
        $cert = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'serial_number' => 'CERT-TEST0001',
            'pdf_path' => null,
        ]);

        $this->getJson("/api/certificates/{$cert->id}/download")
            ->assertStatus(409)->assertJsonPath('code', 'CERTIFICATE_NOT_READY');
    }

    public function test_download_ready_returns_pdf(): void
    {
        $student = User::factory()->create();
        $exam = $this->finalExam();
        $this->passExam($student, $exam, $this->addQuestion($exam));
        $cert = Certificate::where('user_id', $student->id)->firstOrFail();

        Sanctum::actingAs($student);
        $this->getJson("/api/certificates/{$cert->id}/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_public_verification_needs_no_auth(): void
    {
        $student = User::factory()->create();
        $exam = $this->finalExam();
        $this->passExam($student, $exam, $this->addQuestion($exam));
        $serial = Certificate::where('user_id', $student->id)->firstOrFail()->serial_number;

        $this->getJson("/api/certificates/verify/{$serial}")
            ->assertOk()
            ->assertJsonPath('data.serial_number', $serial)
            ->assertJsonMissingPath('data.user_id');

        $this->getJson('/api/certificates/verify/CERT-NOPE')->assertNotFound();
    }

    public function test_mine_requires_auth_and_returns_own_only(): void
    {
        $this->getJson('/api/certificates/mine')->assertUnauthorized();

        $student = User::factory()->create();
        Sanctum::actingAs($student);
        $this->getJson('/api/certificates/mine')->assertOk()->assertJsonPath('data', []);
    }
}
