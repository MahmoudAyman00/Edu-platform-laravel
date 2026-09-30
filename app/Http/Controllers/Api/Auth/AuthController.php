<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Services\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $auth)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->auth->register($request->validated());

        return $this->success(new AuthResource($user), 'messages.auth.registered', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->string('email')->toString(), $request->string('password')->toString());

        return $this->success(
            new AuthResource($result['user'], $result['token']),
            'messages.auth.logged_in'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return $this->success(null, 'messages.auth.logged_out');
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->auth->verifyEmail(
            (int) $request->integer('id'),
            (string) $request->string('hash'),
        );

        return $this->success(new AuthResource($user), 'messages.auth.email_verified');
    }

    public function resendVerification(ForgotPasswordRequest $request): JsonResponse
    {
        $this->auth->resendVerification($request->string('email')->toString());

        return $this->success(null, 'messages.auth.verification_resent');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->auth->forgotPassword($request->string('email')->toString());

        return $this->success(null, 'messages.auth.password_email_sent');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->auth->resetPassword(
            $request->string('email')->toString(),
            $request->string('token')->toString(),
            $request->string('password')->toString(),
        );

        return $this->success(null, 'messages.auth.password_reset');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new AuthResource($request->user()), 'messages.users.profile');
    }
}
