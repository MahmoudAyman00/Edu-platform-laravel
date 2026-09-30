<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Enrollments\EnrollmentResource;
use App\Models\Course;
use App\Services\Enrollments\EnrollmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    use ApiResponse;

    public function __construct(protected EnrollmentService $enrollments)
    {
    }

    public function enroll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $enrollment = $this->enrollments->enrollFree(
            $request->user(),
            Course::findOrFail($data['course_id'])
        );

        return $this->success(
            new EnrollmentResource($enrollment->load('course.translations')),
            'messages.enrollments.enrolled',
            201
        );
    }

    public function mine(Request $request): JsonResponse
    {
        $enrollments = $this->enrollments->listActiveEnrollments($request->user());

        return $this->success(
            EnrollmentResource::collection($enrollments),
            'messages.enrollments.list'
        );
    }
}
