<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function seedNotification(User $user, string $key = 'enrolled', array $params = []): string
    {
        $notification = $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'test',
            'data' => ['key' => $key, 'params' => $params + ['course' => 'Laravel', 'name' => $user->name]],
        ]);

        return $notification->getKey();
    }

    public function test_list_notifications_translates_key_params(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);
        Sanctum::actingAs($user);
        $this->seedNotification($user);

        $response = $this->getJson('/api/notifications');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('data'));
        $this->assertNotEmpty($response->json('data.0.title'));
    }

    public function test_unread_count_and_mark_flows(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $id = $this->seedNotification($user);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->patchJson("/api/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->seedNotification($user);
        $this->seedNotification($user);

        $this->postJson('/api/notifications/read-all')->assertOk();

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }
}
