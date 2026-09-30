<?php

namespace Tests\Feature;

use App\Events\Auth\PasswordResetRequested;
use App\Events\Auth\UserRegistered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_event_is_dispatched(): void
    {
        Event::fake([UserRegistered::class]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'locale' => 'ar',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        Event::assertDispatched(UserRegistered::class);
        $this->assertDatabaseHas('users', ['email' => 'student@example.com']);
    }

    public function test_unverified_user_cannot_login(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_verified_user_can_login_and_logout(): void
    {
        User::factory()->create([
            'email' => 'verified@example.com',
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'verified@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()->assertJsonPath('success', true);
        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_verify_email_with_correct_hash(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->postJson('/api/auth/verify-email', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_forgot_password_dispatches_event(): void
    {
        Event::fake([PasswordResetRequested::class]);

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('success', true);

        Event::assertDispatched(PasswordResetRequested::class);
    }

    public function test_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'change@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'new-password123',
        ])->assertOk();
    }
}
