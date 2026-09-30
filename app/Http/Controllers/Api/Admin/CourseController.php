<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\StoreCourseRequest;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Http\Resources\Courses\CourseDetailResource;
use App\Http\Resources\Courses\CourseResource;
use App\Models\Course;
use App\Services\Courses\CourseService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ApiResponse;

    public function __construct(protected CourseService $courses)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->courses->listForAdmin(
            $request->only(['status', 'category_id', 'search']),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginated(
            $paginator->through(fn (Course $c) => (new CourseResource($c))->toArray($request)),
            'messages.courses.list',
        );
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        return $this->success(
            new CourseDetailResource($this->courses->create($request->validated())),
            'messages.courses.created',
            201
        );
    }

    public function show(Course $course): JsonResponse
    {
        return $this->success(
            new CourseDetailResource($this->courses->showForAdmin($course->id)),
            'messages.courses.detail'
        );
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        return $this->success(
            new CourseDetailResource($this->courses->update($course, $request->validated())),
            'messages.courses.updated'
        );
    }

    public function publish(Course $course): JsonResponse
    {
        return $this->success(
            new CourseDetailResource($this->courses->publish($course)),
            'messages.courses.published'
        );
    }

    public function archive(Course $course): JsonResponse
    {
        return $this->success(
            new CourseDetailResource($this->courses->archive($course)),
            'messages.courses.archived'
        );
    }
}
