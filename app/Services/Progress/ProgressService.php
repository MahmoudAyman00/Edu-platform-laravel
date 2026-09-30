<?php

namespace App\Services\Progress;

use App\Exceptions\AppException;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\Enrollments\EnrollmentService;

class ProgressService
{
    public function __construct(protected EnrollmentService $enrollments)
    {
    }

    public function markLessonCompleted(User $user, Lesson $lesson): LessonProgress
    {
        $this->requireAccess($user, $lesson);

        return LessonProgress::updateOrCreate(
            ['user_id' => $user->getKey(), 'lesson_id' => $lesson->getKey()],
            ['completed_at' => now()]
        );
    }

    /**
     * @return array{course_id: int, total_lessons: int, completed_lessons: int, completed_lesson_ids: array<int, int>, percent: int}
     */
    public function getCourseProgress(User $user, Course $course): array
    {
        if (! $this->enrollments->hasAccess($user, $course)) {
            throw AppException::fromKey('messages.enrollments.no_access', 'ENROLLMENT_REQUIRED', 403);
        }

        $lessonIds = $course->lessons()->pluck('lessons.id')->all();
        $total = count($lessonIds);

        $completedIds = LessonProgress::query()
            ->where('user_id', $user->getKey())
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')->map(fn ($id) => (int) $id)->values()->all();

        $completed = count($completedIds);

        return [
            'course_id' => $course->getKey(),
            'total_lessons' => $total,
            'completed_lessons' => $completed,
            'completed_lesson_ids' => $completedIds,
            'percent' => $total > 0 ? (int) round($completed / $total * 100) : 0,
        ];
    }

    /**
     * @return array<int, array{course: Course, progress: array}>
     */
    public function listMyCoursesWithProgress(User $user): array
    {
        $result = [];

        foreach ($this->enrollments->listActiveEnrollments($user) as $enrollment) {
            $result[] = [
                'course' => $enrollment->course,
                'progress' => $this->getCourseProgress($user, $enrollment->course),
            ];
        }

        return $result;
    }

    private function requireAccess(User $user, Lesson $lesson): void
    {
        if (! $this->enrollments->hasAccess($user, $lesson->course)) {
            throw AppException::fromKey('messages.enrollments.no_access', 'ENROLLMENT_REQUIRED', 403);
        }
    }
}
