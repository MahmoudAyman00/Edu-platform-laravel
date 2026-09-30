<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Progress\CourseProgressResource;
use App\Http\Resources\Progress\MyCourseResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\Progress\ProgressService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    use ApiResponse;

    public function __construct(protected ProgressService $progress)
    {
    }

    public function complete(Request $request, Lesson $lesson): JsonResponse
    {
        $this->progress->markLessonCompleted($request->user(), $lesson);

        $course = $lesson->course->load(['translations', 'category.translations']);

        return $this->success(
            new CourseProgressResource([
                'course' => $course,
                'progress' => $this->progress->getCourseProgress($request->user(), $lesson->course),
            ]),
            'messages.progress.completed'
        );
    }

    public function show(Request $request, int $course): JsonResponse
    {
        $courseModel = Course::with(['translations', 'category.translations'])->findOrFail($course);

        return $this->success(
            new CourseProgressResource([
                'course' => $courseModel,
                'progress' => $this->progress->getCourseProgress($request->user(), $courseModel),
            ]),
            'messages.progress.detail'
        );
    }

    public function myCourses(Request $request): JsonResponse
    {
        return $this->success(
            MyCourseResource::collection($this->progress->listMyCoursesWithProgress($request->user())),
            'messages.progress.my_courses'
        );
    }
}
