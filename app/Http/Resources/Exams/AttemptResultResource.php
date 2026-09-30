<?php

namespace App\Http\Resources\Exams;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttemptResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'score' => $this->score,
            'passed' => $this->passed,
            'started_at' => $this->started_at,
            'expires_at' => $this->expires_at,
            'submitted_at' => $this->submitted_at,
            'answers' => $this->whenLoaded(
                'answers',
                fn () => $this->answers->map(fn ($a) => [
                    'question_id' => $a->question_id,
                    'question_text' => $a->question?->translation()?->text,
                    'selected_option_id' => $a->selected_option_id,
                    'selected_text' => $a->selectedOption?->translation()?->text,
                    'is_correct' => (bool) $a->is_correct,
                    'points_awarded' => $a->points_awarded,
                ])->values()
            ),
        ];
    }
}
