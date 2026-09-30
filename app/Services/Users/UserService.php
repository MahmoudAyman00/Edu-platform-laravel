<?php

namespace App\Services\Users;

use App\Enums\UserRole;
use App\Exceptions\AppException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Laravel\Sanctum\PersonalAccessToken;

class UserService
{
    public function getProfile(User $user): User
    {
        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill(collect($data)->only(['name', 'locale'])->toArray());

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        if (! empty($data['password'])) {
            // Password change kills every other session, current one survives.
            $current = $user->currentAccessToken();
            $tokens = $user->tokens();

            if ($current instanceof PersonalAccessToken) {
                $tokens->where('id', '!=', $current->id);
            }

            $tokens->delete();
        }

        return $user->refresh();
    }

    public function adminList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($qq) => $qq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->latest()
            ->paginate($perPage);
    }

    public function adminCreate(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::from($data['role']),
            'locale' => $data['locale'] ?? config('app.locale', 'ar'),
            'email_verified_at' => now(),
        ]);
    }

    public function adminUpdate(User $user, array $data): User
    {
        if (array_key_exists('role', $data)) {
            $data['role'] = UserRole::from($data['role']);
        }

        $user->fill(collect($data)->only(['name', 'role', 'locale'])->toArray());

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return $user->refresh();
    }

    public function adminDelete(User $admin, User $user): void
    {
        if ($admin->is($user)) {
            throw AppException::fromKey('messages.users.cannot_delete_self', 'CANNOT_DELETE_SELF', 400);
        }

        // Soft delete + revoke all API tokens + block login.
        $user->tokens()->delete();
        $user->delete();
    }
}
