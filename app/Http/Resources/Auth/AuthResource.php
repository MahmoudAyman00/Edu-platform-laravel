<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\Users\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function __construct($resource, protected ?string $token = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
            'token' => $this->token,
        ];
    }
}
