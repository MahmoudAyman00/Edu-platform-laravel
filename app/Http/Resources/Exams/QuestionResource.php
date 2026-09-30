<?php

namespace App\Http\Resources\Exams;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = (bool) $request->user()?->isAdmin();

        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'points' => $this->points,
            'sort' => $this->sort,
            'text' => $this->translation()?->text,
            'translations' => $this->whenLoaded(
                'translations',
                fn () => $this->translations->mapWithKeys(fn ($t) => [
                    $t->locale => ['text' => $t->text],
                ])
            ),
            'options' => $this->whenLoaded(
                'options',
                fn () => $this->options->map(fn ($o) => array_filter([
                    'id' => $o->id,
                    'text' => $o->translation()?->text,
                    'is_correct' => $isAdmin ? (bool) $o->is_correct : null,
                ], fn ($v) => $v !== null))
            ),
        ];
    }
}
