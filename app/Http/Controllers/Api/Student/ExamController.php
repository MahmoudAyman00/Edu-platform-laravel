<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\SubmitAttemptRequest;
use App\Http\Resources\Exams\AttemptResultResource;
use App\Http\Resources\Exams\ExamResource;
use App\Models\Course;
use App\Models\Exam;
use App\Services\Exams\AttemptService;
use App\Services\Exams\ExamService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ExamService $exams,
        protected AttemptService $attempts,
    ) {
    }

    public function index(Request $request, Course $course): JsonResponse
    {
        $exams = $this->exams->listCourseExams($request->user(), $course);

        return $this->success(
            ExamResource::collection($exams),
            'messages.exams.list'
        );
    }

    public function show(Request $request, int $exam): JsonResponse
    {
        $user = $request->user();
        $examModel = $this->exams->showForStudent($user, $exam);
        $ongoing = $this->attempts->ongoingAttempt($user, $examModel);

        return $this->success(
            [
                'exam' => new ExamResource($examModel),
                'attempts_left' => $this->attempts->attemptsLeft($user, $examModel),
                'ongoing_attempt' => $ongoing ? new AttemptResultResource($ongoing) : null,
            ],
            'messages.exams.detail'
        );
    }

    public function start(Request $request, Exam $exam): JsonResponse
    {
        return $this->success(
            new AttemptResultResource($this->attempts->start($request->user(), $exam)),
            'messages.exams.attempt_started',
            201
        );
    }

    public function submit(SubmitAttemptRequest $request, Exam $exam): JsonResponse
    {
        return $this->success(
            new AttemptResultResource(
                $this->attempts->submit($request->user(), $exam, $request->validated()['answers'])
            ),
            'messages.exams.attempt_submitted'
        );
    }

    public function result(Request $request, Exam $exam): JsonResponse
    {
        return $this->success(
            new AttemptResultResource($this->attempts->getResult($request->user(), $exam)),
            'messages.exams.detail'
        );
    }
}
