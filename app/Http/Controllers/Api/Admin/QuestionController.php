<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\StoreQuestionRequest;
use App\Http\Requests\Exams\UpdateQuestionRequest;
use App\Http\Resources\Exams\ExamResource;
use App\Http\Resources\Exams\QuestionResource;
use App\Models\Exam;
use App\Models\Question;
use App\Services\Exams\ExamService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    use ApiResponse;

    public function __construct(protected ExamService $exams)
    {
    }

    public function store(StoreQuestionRequest $request, Exam $exam): JsonResponse
    {
        return $this->success(
            new QuestionResource($this->exams->addQuestion($exam, $request->validated())),
            'messages.exams.question_added',
            201
        );
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        return $this->success(
            new QuestionResource($this->exams->updateQuestion($question, $request->validated())),
            'messages.exams.question_updated'
        );
    }

    public function destroy(Question $question): JsonResponse
    {
        $this->exams->deleteQuestion($question);

        return $this->success(null, 'messages.exams.question_deleted');
    }

    public function reorder(Request $request, Exam $exam): JsonResponse
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'min:1'],
        ]);

        return $this->success(
            new ExamResource($this->exams->reorderQuestions($exam, $data['ordered_ids'])),
            'messages.exams.questions_reordered'
        );
    }
}
