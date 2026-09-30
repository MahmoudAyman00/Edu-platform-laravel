<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\StoreExamRequest;
use App\Http\Requests\Exams\UpdateExamRequest;
use App\Http\Resources\Exams\ExamResource;
use App\Models\Exam;
use App\Services\Exams\ExamService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    use ApiResponse;

    public function __construct(protected ExamService $exams)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->exams->listForAdmin(
            $request->only(['course_id', 'status']),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginated(
            $paginator->through(fn (Exam $e) => (new ExamResource($e))->toArray($request)),
            'messages.exams.list',
        );
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        return $this->success(
            new ExamResource($this->exams->create($request->validated())),
            'messages.exams.created',
            201
        );
    }

    public function show(Exam $exam): JsonResponse
    {
        return $this->success(
            new ExamResource($this->exams->showForAdmin($exam->id)),
            'messages.exams.detail'
        );
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        return $this->success(
            new ExamResource($this->exams->update($exam, $request->validated())),
            'messages.exams.updated'
        );
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $this->exams->delete($exam);

        return $this->success(null, 'messages.exams.deleted');
    }

    public function publish(Exam $exam): JsonResponse
    {
        return $this->success(
            new ExamResource($this->exams->publish($exam)),
            'messages.exams.published'
        );
    }

    public function setFinal(Request $request, Exam $exam): JsonResponse
    {
        $data = $request->validate([
            'is_final' => ['required', 'boolean'],
        ]);

        return $this->success(
            new ExamResource($this->exams->setFinal($exam, (bool) $data['is_final'])),
            'messages.exams.final_set'
        );
    }
}
