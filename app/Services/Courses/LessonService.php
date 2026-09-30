<?php

namespace App\Services\Courses;

use App\Exceptions\AppException;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\Concerns\SyncsTranslations;
use App\Support\CloudStorage;
use Illuminate\Support\Str;

class LessonService
{
    use SyncsTranslations;

    public function create(Course $course, array $data): Lesson
    {
        $lesson = $course->lessons()->create([
            'video_path' => $data['video_path'] ?? null,
            'duration' => $data['duration'] ?? null,
            'sort' => $data['sort'] ?? ((int) $course->lessons()->max('sort') + 1),
        ]);

        $this->syncTranslations($lesson, $data['translations']);

        return $lesson->load('translations');
    }

    public function update(Lesson $lesson, array $data): Lesson
    {
        $lesson->fill(collect($data)->only(['video_path', 'duration', 'sort'])->toArray());
        $lesson->save();

        if (isset($data['translations'])) {
            $this->syncTranslations($lesson, $data['translations']);
        }

        return $lesson->load('translations');
    }

    public function delete(Lesson $lesson): void
    {
        $lesson->delete();
    }

    /**
     * @param  array<int, int>  $orderedIds  Lesson IDs in the desired order.
     */
    public function reorder(Course $course, array $orderedIds): Course
    {
        $ownedIds = $course->lessons()->pluck('id')->all();
        $unknown = array_diff($orderedIds, $ownedIds);

        if ($unknown !== [] || count($orderedIds) !== count(array_unique($orderedIds))) {
            throw AppException::fromKey('messages.lessons.invalid_order', 'INVALID_LESSON_ORDER', 422);
        }

        foreach (array_values($orderedIds) as $position => $id) {
            Lesson::whereKey($id)->update(['sort' => $position + 1]);
        }

        return $course->load(['translations', 'category.translations', 'lessons.translations']);
    }

    /**
     * @return array{upload_url: string, path: string, expires_in: int}
     */
    public function generateUploadUrl(Course $course, string $filename): array
    {
        $ttl = (int) config('content.signed_url_ttl', 3600);
        $safe = Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'-'.Str::uuid().'.'.strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $path = "courses/{$course->getKey()}/lessons/{$safe}";

        return [
            'upload_url' => CloudStorage::presignedUploadUrl($path, $ttl),
            'path' => $path,
            'expires_in' => $ttl,
        ];
    }
}
