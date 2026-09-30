<?php

namespace App\Http\Resources\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return (new CourseProgressResource($this->resource))->toArray($request);
    }
}
