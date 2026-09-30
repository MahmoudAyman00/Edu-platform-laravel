<?php

namespace App\Http\Resources\Courses;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->translation();

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'has_video' => ! empty($this->video_path),
            'duration' => $this->duration,
            'sort' => $this->sort,
            'translations' => $this->whenLoaded(
                'translations',
                fn () => $this->translations->mapWithKeys(fn ($t) => [
                    $t->locale => ['title' => $t->title, 'description' => $t->description],
                ])
            ),
        ];
    }
}
