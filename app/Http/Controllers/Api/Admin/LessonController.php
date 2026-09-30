<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\StoreLessonRequest;
use App\Http\Requests\Courses\UpdateLessonRequest;
use App\Http\Resources\Courses\CourseDetailResource;
use App\Http\Resources\Courses\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\Courses\LessonService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    use ApiResponse;

    public function __construct(protected LessonService $lessons)
    {
    }

    public function store(StoreLessonRequest $request, Course $course): JsonResponse
    {
        return $this->success(
            new LessonResource($this->lessons->create($course, $request->validated())),
            'messages.lessons.created',
            201
        );
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): JsonResponse
    {
        return $this->success(
            new LessonResource($this->lessons->update($lesson, $request->validated())),
            'messages.lessons.updated'
        );
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $this->lessons->delete($lesson);

        return $this->success(null, 'messages.lessons.deleted');
    }

    public function reorder(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'min:1'],
        ]);

        return $this->success(
            new CourseDetailResource($this->lessons->reorder($course, $data['ordered_ids'])),
            'messages.lessons.reordered'
        );
    }

    public function uploadUrl(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255', 'regex:/\.(mp4|mov|avi|mkv|webm)$/i'],
        ]);

        return $this->success(
            $this->lessons->generateUploadUrl($course, $data['filename']),
            'messages.lessons.upload_url'
        );
    }
}
