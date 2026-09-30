<?php

namespace App\Http\Resources\Certificates;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificatePublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'student_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'course_title' => $this->whenLoaded('course', fn () => $this->course->translation()?->title),
            'serial_number' => $this->serial_number,
            'issued_at' => $this->created_at,
        ];
    }
}
