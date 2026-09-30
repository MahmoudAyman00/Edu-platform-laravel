<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_user_can_view_and_update_own_profile(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->putJson('/api/me', ['name' => 'New Name', 'locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.locale', 'en');
    }

    public function test_admin_can_list_update_and_delete_users(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->putJson("/api/users/{$student->id}", ['name' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->deleteJson("/api/users/{$student->id}")
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $student->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/users/{$admin->id}")
            ->assertBadRequest()
            ->assertJsonPath('code', 'CANNOT_DELETE_SELF');
    }

    public function test_student_cannot_list_users(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::STUDENT]));

        $this->getJson('/api/users')->assertForbidden();
    }

    public function test_admin_can_create_verified_user(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->postJson('/api/users', [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'password' => 'password123',
            'role' => 'student',
            'locale' => 'ar',
        ])->assertCreated()->assertJsonPath('success', true);

        $user = User::findOrFail($response->json('data.id'));
        $this->assertNotNull($user->email_verified_at);

        // actingAs() switches the default guard to sanctum (no once()); restore web guard for login.
        Auth::shouldUse('web');

        $this->postJson('/api/auth/login', [
            'email' => 'newstudent@example.com',
            'password' => 'password123',
        ])->assertOk();
    }

    public function test_student_cannot_create_user(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/users', [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'password123',
            'role' => 'student',
        ])->assertForbidden();
    }

    public function test_deleted_user_cannot_login_and_cannot_reregister(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['email' => 'gone@example.com']);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/users/{$student->id}")->assertOk();

        // actingAs() switches the default guard to sanctum (no once()); restore web guard for login.
        Auth::shouldUse('web');

        $this->postJson('/api/auth/login', [
            'email' => 'gone@example.com',
            'password' => 'password',
        ])->assertUnauthorized()->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $this->postJson('/api/auth/register', [
            'name' => 'Again',
            'email' => 'gone@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_password_change_requires_current_password(): void
    {
        Sanctum::actingAs(User::factory()->create(['password' => 'old-password123']));

        $this->putJson('/api/me', [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertStatus(422);

        $this->putJson('/api/me', [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
            'current_password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_password_change_revokes_other_tokens_but_keeps_current(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);
        $tokenA = $user->createToken('a')->plainTextToken;
        $tokenB = $user->createToken('b')->plainTextToken;

        $this->withToken($tokenA)->putJson('/api/me', [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
            'current_password' => 'old-password123',
        ])->assertOk();

        $this->withToken($tokenA)->getJson('/api/me')->assertOk();

        // Guards are singletons per test: force re-authentication for the next token.
        Auth::forgetGuards();

        $this->withToken($tokenB)->getJson('/api/me')->assertUnauthorized();
    }
}
