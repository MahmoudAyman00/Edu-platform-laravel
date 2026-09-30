<?php

namespace App\Http\Resources\Courses;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->translation();

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_title' => $this->whenLoaded('category', fn () => $this->category->translation()?->title),
            'title' => $translation?->title,
            'description' => $translation?->description,
            'price' => $this->price,
            'is_free' => (bool) $this->is_free,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'lessons_count' => $this->whenCounted('lessons'),
            'created_at' => $this->created_at,
        ];
    }
}
