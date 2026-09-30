<?php

namespace App\Http\Resources\Exams;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->translation();

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_title' => $this->whenLoaded('course', fn () => $this->course->translation()?->title),
            'title' => $translation?->title,
            'description' => $translation?->description,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'is_final' => (bool) $this->is_final,
            'pass_score' => $this->pass_score,
            'max_attempts' => $this->max_attempts,
            'duration_minutes' => $this->duration_minutes,
            'questions_count' => $this->whenCounted('questions'),
            'translations' => $this->whenLoaded(
                'translations',
                fn () => $this->translations->mapWithKeys(fn ($t) => [
                    $t->locale => ['title' => $t->title, 'description' => $t->description],
                ])
            ),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at,
        ];
    }
}
