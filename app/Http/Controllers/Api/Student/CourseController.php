<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Courses\CourseDetailResource;
use App\Http\Resources\Courses\CourseResource;
use App\Exceptions\AppException;
use App\Models\Course;
use App\Services\Courses\CourseService;
use App\Services\Enrollments\EnrollmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CourseService $courses,
        protected EnrollmentService $enrollments,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->courses->listForStudent(
            $request->only(['category_id', 'search']),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginated(
            $paginator->through(fn (Course $c) => (new CourseResource($c))->toArray($request)),
            'messages.courses.list',
        );
    }

    public function show(Request $request, int $course): JsonResponse
    {
        $courseModel = $this->courses->showForStudent($course);

        // Archived courses stay open for enrolled students only.
        if ($courseModel->status->value === 'ARCHIVED'
            && ! $this->enrollments->hasAccess($request->user(), $courseModel)) {
            throw AppException::fromKey('messages.enrollments.no_access', 'ENROLLMENT_REQUIRED', 403);
        }

        return $this->success(
            new CourseDetailResource($courseModel),
            'messages.courses.detail'
        );
    }
}
