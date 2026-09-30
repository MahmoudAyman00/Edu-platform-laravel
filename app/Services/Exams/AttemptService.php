<?php

namespace App\Services\Exams;

use App\Enums\AttemptStatus;
use App\Events\Exams\ExamAttemptGraded;
use App\Exceptions\AppException;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\Enrollments\EnrollmentService;

class AttemptService
{
    public function __construct(protected EnrollmentService $enrollments)
    {
    }

    public function start(User $user, Exam $exam): ExamAttempt
    {
        $this->requirePublishedAndEnrolled($user, $exam);
        $this->closeExpiredOngoing($user, $exam);

        $ongoing = $this->ongoingAttempt($user, $exam);

        if ($ongoing) {
            return $ongoing;
        }

        if ($this->attemptsLeft($user, $exam) <= 0) {
            throw AppException::fromKey('messages.exams.max_attempts', 'MAX_ATTEMPTS_REACHED', 403);
        }

        $now = now();

        return ExamAttempt::create([
            'exam_id' => $exam->getKey(),
            'user_id' => $user->getKey(),
            'status' => AttemptStatus::IN_PROGRESS,
            'started_at' => $now,
            'expires_at' => $exam->duration_minutes ? $now->copy()->addMinutes($exam->duration_minutes) : null,
        ]);
    }

    /**
     * @param  array<int, array{question_id: int, selected_option_id?: ?int}>  $answers
     */
    public function submit(User $user, Exam $exam, array $answers): ExamAttempt
    {
        $this->requirePublishedAndEnrolled($user, $exam);

        $attempt = $this->ongoingAttempt($user, $exam);

        if (! $attempt) {
            throw AppException::fromKey('messages.exams.no_attempt', 'NO_ACTIVE_ATTEMPT', 400);
        }

        if ($this->isPastGrace($attempt)) {
            $attempt->forceFill([
                'status' => AttemptStatus::EXPIRED,
                'score' => 0,
                'passed' => false,
                'submitted_at' => now(),
            ])->save();

            throw AppException::fromKey('messages.exams.attempt_expired', 'ATTEMPT_EXPIRED', 400);
        }

        $questions = $exam->questions()->with('options')->get()->keyBy('id');
        $unknown = collect($answers)->pluck('question_id')->diff($questions->keys());

        if ($unknown->isNotEmpty()) {
            throw AppException::fromKey('messages.exams.invalid_answers', 'INVALID_ANSWERS', 422);
        }

        $awarded = 0;
        $total = 0;

        foreach ($questions as $question) {
            $total += $question->points;
            $given = collect($answers)->firstWhere('question_id', $question->id);
            $selectedId = $given['selected_option_id'] ?? null;

            $option = $selectedId ? $question->options->firstWhere('id', (int) $selectedId) : null;
            $correct = $option && $option->is_correct;
            $points = $correct ? $question->points : 0;
            $awarded += $points;

            $attempt->answers()->updateOrCreate(
                ['question_id' => $question->id],
                [
                    'selected_option_id' => $option?->id,
                    'is_correct' => $correct,
                    'points_awarded' => $points,
                ]
            );
        }

        $score = $total > 0 ? (int) round($awarded / $total * 100) : 0;

        $attempt->forceFill([
            'status' => AttemptStatus::SUBMITTED,
            'score' => $score,
            'passed' => $score >= $exam->pass_score,
            'submitted_at' => now(),
        ])->save();

        ExamAttemptGraded::dispatch($attempt->load(['user', 'exam.translations']));

        return $attempt->load(['answers.question.translations', 'answers.selectedOption.translations', 'exam.translations']);
    }

    public function getResult(User $user, Exam $exam): ExamAttempt
    {
        $this->requirePublishedAndEnrolled($user, $exam);

        $attempt = ExamAttempt::query()
            ->where('exam_id', $exam->getKey())
            ->where('user_id', $user->getKey())
            ->where('status', '!=', AttemptStatus::IN_PROGRESS)
            ->latest('id')
            ->first();

        if (! $attempt) {
            throw AppException::fromKey('messages.exams.no_result', 'NO_RESULT', 404);
        }

        return $attempt->load(['answers.question.translations', 'answers.selectedOption.translations', 'exam.translations']);
    }

    public function attemptsLeft(User $user, Exam $exam): int
    {
        $consumed = ExamAttempt::query()
            ->where('exam_id', $exam->getKey())
            ->where('user_id', $user->getKey())
            ->where('status', '!=', AttemptStatus::IN_PROGRESS)
            ->count();

        return max(0, $exam->max_attempts - $consumed);
    }

    public function expireStale(): int
    {
        $grace = (int) config('exams.attempt_grace_seconds', 60);

        return ExamAttempt::query()
            ->where('status', AttemptStatus::IN_PROGRESS)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subSeconds($grace))
            ->update([
                'status' => AttemptStatus::EXPIRED,
                'score' => 0,
                'passed' => false,
                'submitted_at' => now(),
            ]);
    }

    private function requirePublishedAndEnrolled(User $user, Exam $exam): void
    {
        if ($exam->status->value !== 'PUBLISHED') {
            throw AppException::fromKey('messages.exams.not_published', 'EXAM_NOT_PUBLISHED', 400);
        }

        if (! $this->enrollments->hasAccess($user, $exam->course)) {
            throw AppException::fromKey('messages.enrollments.no_access', 'ENROLLMENT_REQUIRED', 403);
        }
    }

    public function ongoingAttempt(User $user, Exam $exam): ?ExamAttempt
    {
        $grace = (int) config('exams.attempt_grace_seconds', 60);

        return ExamAttempt::query()
            ->where('exam_id', $exam->getKey())
            ->where('user_id', $user->getKey())
            ->where('status', AttemptStatus::IN_PROGRESS)
            ->where(fn ($q) => $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()->subSeconds($grace)))
            ->latest('id')
            ->first();
    }

    private function closeExpiredOngoing(User $user, Exam $exam): void
    {
        $grace = (int) config('exams.attempt_grace_seconds', 60);

        ExamAttempt::query()
            ->where('exam_id', $exam->getKey())
            ->where('user_id', $user->getKey())
            ->where('status', AttemptStatus::IN_PROGRESS)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subSeconds($grace))
            ->update([
                'status' => AttemptStatus::EXPIRED,
                'score' => 0,
                'passed' => false,
                'submitted_at' => now(),
            ]);
    }

    private function isPastGrace(ExamAttempt $attempt): bool
    {
        if (! $attempt->expires_at) {
            return false;
        }

        $grace = (int) config('exams.attempt_grace_seconds', 60);

        return now()->greaterThan($attempt->expires_at->copy()->addSeconds($grace));
    }
}
