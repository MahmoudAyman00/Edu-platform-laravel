<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Events\Auth\PasswordResetRequested;
use App\Events\Auth\UserRegistered;
use App\Events\Auth\VerificationEmailRequested;
use App\Exceptions\AppException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::STUDENT,
            'locale' => $data['locale'] ?? config('app.locale', 'ar'),
        ]);

        UserRegistered::dispatch($user);

        return $user;
    }

    /**
     * @return array{user: User, token: string}
     */
    public function login(string $email, string $password): array
    {
        // once(): stateless credential check, no session is created (API-only).
        if (! Auth::once(['email' => $email, 'password' => $password])) {
            throw AppException::fromKey('messages.auth.invalid_credentials', 'INVALID_CREDENTIALS', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            throw AppException::fromKey('messages.auth.email_not_verified', 'EMAIL_NOT_VERIFIED', 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        // Only real DB tokens can be revoked (session-based TransientToken has nothing to delete).
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    public function verifyEmail(int $id, string $hash): User
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            throw AppException::fromKey('messages.auth.email_already_verified', 'ALREADY_VERIFIED', 400);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw AppException::fromKey('messages.auth.invalid_token', 'INVALID_TOKEN', 400);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    public function resendVerification(string $email): void
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            throw AppException::fromKey('messages.auth.email_already_verified', 'ALREADY_VERIFIED', 400);
        }

        VerificationEmailRequested::dispatch($user);
    }

    public function forgotPassword(string $email): void
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        // Always succeed publicly to avoid email enumeration.
        if (! $user) {
            return;
        }

        $token = Password::createToken($user);

        PasswordResetRequested::dispatch($user, $token);
    }

    public function resetPassword(string $email, string $token, string $password): void
    {
        $status = Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function (User $user) use ($password) {
                $user->forceFill(['password' => $password, 'remember_token' => null])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw AppException::fromKey('messages.auth.invalid_token', 'INVALID_TOKEN', 400);
        }
    }
}
