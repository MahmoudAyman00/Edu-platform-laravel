<?php

namespace App\Http\Resources\Progress;

use App\Http\Resources\Courses\CourseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'course' => new CourseResource($this['course']),
            'total_lessons' => $this['progress']['total_lessons'],
            'completed_lessons' => $this['progress']['completed_lessons'],
            'completed_lesson_ids' => $this['progress']['completed_lesson_ids'] ?? [],
            'percent' => $this['progress']['percent'],
        ];
    }
}
