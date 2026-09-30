<?php

namespace App\Services\Exams;

use App\Enums\ExamStatus;
use App\Exceptions\AppException;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Services\Concerns\SyncsTranslations;
use App\Services\Enrollments\EnrollmentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExamService
{
    use SyncsTranslations;

    public function __construct(protected EnrollmentService $enrollments)
    {
    }

    public function listForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $status = null;

        if (isset($filters['status'])) {
            // Case-insensitive: draft/DRAFT both work.
            $status = ExamStatus::tryFrom(strtoupper((string) $filters['status']));

            if (! $status) {
                throw AppException::fromKey('messages.exams.invalid_status', 'INVALID_STATUS', 422);
            }
        }

        return Exam::query()
            ->when($filters['course_id'] ?? null, fn ($q, $id) => $q->where('course_id', $id))
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->with(['translations', 'course.translations'])
            ->withCount('questions')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Exam>
     */
    public function listCourseExams(User $user, Course $course): \Illuminate\Database\Eloquent\Collection
    {
        $this->requireAccess($user, $course);

        return Exam::query()
            ->where('course_id', $course->getKey())
            ->where('status', ExamStatus::PUBLISHED)
            ->with('translations')
            ->withCount('questions')
            ->orderBy('id')
            ->get();
    }

    public function showForAdmin(int $id): Exam
    {
        return Exam::with(['translations', 'course.translations', 'questions.translations', 'questions.options.translations'])
            ->findOrFail($id);
    }

    public function showForStudent(User $user, int $id): Exam
    {
        $exam = Exam::where('status', ExamStatus::PUBLISHED)
            ->with(['translations', 'course.translations', 'questions.translations', 'questions.options.translations'])
            ->findOrFail($id);

        $this->requireAccess($user, $exam->course);

        return $exam;
    }

    public function create(array $data): Exam
    {
        $exam = Exam::create([
            'course_id' => $data['course_id'],
            'status' => ExamStatus::DRAFT,
            'is_final' => (bool) ($data['is_final'] ?? false),
            'pass_score' => $data['pass_score'] ?? 60,
            'max_attempts' => $data['max_attempts'] ?? 3,
            'duration_minutes' => $data['duration_minutes'] ?? null,
        ]);

        if ($exam->is_final) {
            $this->unsetOtherFinals($exam);
        }

        $this->syncTranslations($exam, $data['translations']);

        return $exam->load(['translations', 'course.translations']);
    }

    public function update(Exam $exam, array $data): Exam
    {
        $exam->fill(collect($data)->only([
            'course_id', 'pass_score', 'max_attempts', 'duration_minutes',
        ])->toArray());
        $exam->save();

        if (isset($data['translations'])) {
            $this->syncTranslations($exam, $data['translations']);
        }

        return $exam->load(['translations', 'course.translations']);
    }

    public function publish(Exam $exam): Exam
    {
        $exam->forceFill(['status' => ExamStatus::PUBLISHED])->save();

        return $exam->load(['translations', 'course.translations']);
    }

    public function setFinal(Exam $exam, bool $isFinal): Exam
    {
        $exam->forceFill(['is_final' => $isFinal])->save();

        if ($isFinal) {
            $this->unsetOtherFinals($exam);
        }

        return $exam->load(['translations', 'course.translations']);
    }

    public function delete(Exam $exam): void
    {
        $exam->delete();
    }

    public function addQuestion(Exam $exam, array $data): Question
    {
        $this->assertValidOptions($data['options']);

        $question = $exam->questions()->create([
            'points' => $data['points'],
            'sort' => $data['sort'] ?? ((int) $exam->questions()->max('sort') + 1),
        ]);

        $this->syncTranslations($question, $this->mapQuestionTranslations($data['translations']));
        $this->syncOptions($question, $data['options']);

        return $question->load(['translations', 'options.translations']);
    }

    public function updateQuestion(Question $question, array $data): Question
    {
        $question->fill(collect($data)->only(['points', 'sort'])->toArray());
        $question->save();

        if (isset($data['translations'])) {
            $this->syncTranslations($question, $this->mapQuestionTranslations($data['translations']));
        }

        if (isset($data['options'])) {
            $this->assertValidOptions($data['options']);
            $question->options()->delete();
            $this->syncOptions($question, $data['options']);
        }

        return $question->load(['translations', 'options.translations']);
    }

    public function deleteQuestion(Question $question): void
    {
        $question->delete();
    }

    /**
     * @param  array<int, int>  $orderedIds  Question IDs in the desired order.
     */
    public function reorderQuestions(Exam $exam, array $orderedIds): Exam
    {
        $ownedIds = $exam->questions()->pluck('id')->all();
        $unknown = array_diff($orderedIds, $ownedIds);

        if ($unknown !== [] || count($orderedIds) !== count(array_unique($orderedIds))) {
            throw AppException::fromKey('messages.exams.invalid_order', 'INVALID_QUESTION_ORDER', 422);
        }

        foreach (array_values($orderedIds) as $position => $id) {
            Question::whereKey($id)->update(['sort' => $position + 1]);
        }

        return $exam->load(['translations', 'course.translations', 'questions.translations', 'questions.options.translations']);
    }

    private function requireAccess(User $user, Course $course): void
    {
        if (! $this->enrollments->hasAccess($user, $course)) {
            throw AppException::fromKey('messages.enrollments.no_access', 'ENROLLMENT_REQUIRED', 403);
        }
    }

    private function unsetOtherFinals(Exam $exam): void
    {
        Exam::where('course_id', $exam->course_id)
            ->where('id', '!=', $exam->getKey())
            ->update(['is_final' => false]);
    }

    /**
     * @param  array<int, array>  $options
     */
    private function assertValidOptions(array $options): void
    {
        // Exactly one correct option: submit() carries a single selected_option_id.
        $correct = collect($options)->filter(fn ($o) => ! empty($o['is_correct']))->count();

        if (count($options) < 2 || $correct !== 1) {
            throw AppException::fromKey('messages.exams.invalid_options', 'INVALID_OPTIONS', 422);
        }
    }

    /**
     * @param  array<int, array{locale: string, text: string}>  $translations
     */
    private function mapQuestionTranslations(array $translations): array
    {
        return $translations; // text key passes through; title unused for questions
    }

    /**
     * @param  array<int, array{is_correct: bool, translations: array}>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        foreach ($options as $optionData) {
            $option = $question->options()->create([
                'is_correct' => (bool) ($optionData['is_correct'] ?? false),
            ]);

            $this->syncTranslations($option, $optionData['translations']);
        }
    }
}
