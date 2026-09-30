<?php

namespace App\Http\Resources\Payments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_title' => $this->whenLoaded('course', fn () => $this->course->translation()?->title),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'method' => $this->method instanceof \BackedEnum ? $this->method->value : $this->method,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'merchant_ref' => $this->merchant_ref,
            'fawry_reference' => $this->fawry_reference,
            'payment_url' => $this->meta['payment_url'] ?? null,
            'expires_at' => $this->expires_at,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
