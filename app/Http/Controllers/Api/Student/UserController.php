<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Http\Resources\Users\UserResource;
use App\Services\Users\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserService $users)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($this->users->getProfile($request->user())),
            'messages.users.profile'
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->users->updateProfile($request->user(), $request->validated());

        return $this->success(new UserResource($user), 'messages.users.updated');
    }
}
