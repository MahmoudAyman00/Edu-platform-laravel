<?php

namespace App\Http\Resources\Enrollments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_title' => $this->whenLoaded('course', fn () => $this->course->translation()?->title),
            'source' => $this->source instanceof \BackedEnum ? $this->source->value : $this->source,
            'created_at' => $this->created_at,
        ];
    }
}
