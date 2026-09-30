<?php

namespace App\Http\Resources\Courses;

use Illuminate\Http\Request;

class CourseDetailResource extends CourseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'translations' => $this->whenLoaded(
                'translations',
                fn () => $this->translations->mapWithKeys(fn ($t) => [
                    $t->locale => ['title' => $t->title, 'description' => $t->description],
                ])
            ),
            'lessons' => LessonResource::collection($this->whenLoaded('lessons')),
        ]);
    }
}
