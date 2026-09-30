<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\User;
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

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->users->adminList(
            $request->only(['role', 'search']),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginated(
            $paginator->through(fn (User $u) => (new UserResource($u))->toArray($request)),
            'messages.users.list',
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        return $this->success(
            new UserResource($this->users->adminUpdate($user, $request->validated())),
            'messages.users.user_updated'
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        return $this->success(
            new UserResource($this->users->adminCreate($request->validated())),
            'messages.users.user_created',
            201
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->users->adminDelete($request->user(), $user);

        return $this->success(null, 'messages.users.user_deleted');
    }
}
