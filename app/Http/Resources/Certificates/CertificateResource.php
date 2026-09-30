<?php

namespace App\Http\Resources\Certificates;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'course_id' => $this->course_id,
            'course_title' => $this->whenLoaded('course', fn () => $this->course->translation()?->title),
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'is_ready' => $this->isReady(),
            'download_url' => url("/api/certificates/{$this->getKey()}/download"),
            'issued_at' => $this->created_at,
        ];
    }
}
