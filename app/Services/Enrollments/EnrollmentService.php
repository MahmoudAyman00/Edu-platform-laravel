<?php

namespace App\Services\Enrollments;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentSource;
use App\Events\Enrollments\StudentEnrolled;
use App\Exceptions\AppException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService
{
    public function enrollFree(User $user, Course $course): Enrollment
    {
        if (! $course->is_free) {
            throw AppException::fromKey('messages.enrollments.course_not_free', 'COURSE_NOT_FREE', 400);
        }

        return $this->enroll($user, $course, EnrollmentSource::FREE);
    }

    public function enrollAfterPayment(User $user, Course $course): Enrollment
    {
        return $this->enroll($user, $course, EnrollmentSource::PURCHASE);
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function listActiveEnrollments(User $user): Collection
    {
        // Enrolled students keep archived courses; drafts never appear.
        return Enrollment::query()
            ->where('user_id', $user->getKey())
            ->whereHas('course', fn ($q) => $q->whereIn('status', [CourseStatus::PUBLISHED, CourseStatus::ARCHIVED]))
            ->with(['course.translations', 'course.category.translations'])
            ->latest()
            ->get();
    }

    public function hasAccess(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return Enrollment::query()
            ->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->exists();
    }

    private function enroll(User $user, Course $course, EnrollmentSource $source): Enrollment
    {
        if ($course->status !== CourseStatus::PUBLISHED) {
            throw AppException::fromKey('messages.enrollments.course_not_available', 'COURSE_NOT_AVAILABLE', 400);
        }

        $existing = Enrollment::query()
            ->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->first();

        if ($existing) {
            return $existing->load('course.translations');
        }

        $enrollment = Enrollment::create([
            'user_id' => $user->getKey(),
            'course_id' => $course->getKey(),
            'source' => $source,
        ]);

        StudentEnrolled::dispatch($enrollment->load(['user', 'course.translations']));

        return $enrollment;
    }
}
