<?php

namespace App\Services\Courses;

use App\Enums\CourseStatus;
use App\Events\Courses\CoursePublished;
use App\Exceptions\AppException;
use App\Models\Course;
use App\Services\Concerns\SyncsTranslations;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseService
{
    use SyncsTranslations;

    public function listForStudent(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseListQuery($filters, CourseStatus::PUBLISHED)->paginate($perPage);
    }

    public function listForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $status = null;

        if (isset($filters['status'])) {
            // Case-insensitive: draft/DRAFT both work.
            $status = CourseStatus::tryFrom(strtoupper((string) $filters['status']));

            if (! $status) {
                throw AppException::fromKey('messages.courses.invalid_status', 'INVALID_STATUS', 422);
            }
        }

        return $this->baseListQuery($filters, $status)->paginate($perPage);
    }

    public function showForStudent(int $id): Course
    {
        $course = Course::with(['translations', 'category.translations', 'lessons.translations'])
            ->findOrFail($id);

        // Drafts stay invisible; archived courses are handled by the
        // controller (enrolled students only).
        if ($course->status === CourseStatus::DRAFT) {
            throw new NotFoundHttpException();
        }

        return $course;
    }

    public function showForAdmin(int $id): Course
    {
        return Course::with(['translations', 'category.translations', 'lessons.translations'])
            ->findOrFail($id);
    }

    public function create(array $data): Course
    {
        $course = Course::create([
            'category_id' => $data['category_id'],
            'price' => $data['price'],
            'status' => CourseStatus::DRAFT,
        ]);

        $this->syncTranslations($course, $data['translations']);

        return $course->load(['translations', 'category.translations']);
    }

    public function update(Course $course, array $data): Course
    {
        $course->fill(collect($data)->only(['category_id', 'price'])->toArray());
        $course->save();

        if (isset($data['translations'])) {
            $this->syncTranslations($course, $data['translations']);
        }

        return $course->load(['translations', 'category.translations']);
    }

    public function publish(Course $course): Course
    {
        if ($course->status !== CourseStatus::PUBLISHED) {
            $course->forceFill(['status' => CourseStatus::PUBLISHED])->save();

            CoursePublished::dispatch($course->refresh());
        }

        return $course->load(['translations', 'category.translations']);
    }

    public function archive(Course $course): Course
    {
        $course->forceFill(['status' => CourseStatus::ARCHIVED])->save();

        return $course->load(['translations', 'category.translations']);
    }

    private function baseListQuery(array $filters, ?CourseStatus $status): \Illuminate\Database\Eloquent\Builder
    {
        return Course::query()
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereHas(
                'translations',
                fn ($qq) => $qq->where('title', 'like', "%{$s}%")
            ))
            ->with(['translations', 'category.translations'])
            ->withCount('lessons')
            ->latest();
    }
}
