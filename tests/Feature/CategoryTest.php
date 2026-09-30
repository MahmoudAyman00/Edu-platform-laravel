<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function translations(string $title = 'Title'): array
    {
        return [
            ['locale' => 'ar', 'title' => $title.' عربي', 'description' => 'وصف'],
            ['locale' => 'en', 'title' => $title.' EN', 'description' => 'Description'],
        ];
    }

    public function test_admin_can_create_and_update_category(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $created = $this->postJson('/api/categories', [
            'translations' => $this->translations('Design'),
        ])->assertCreated()->assertJsonPath('success', true);

        $id = $created->json('data.id');
        $this->assertDatabaseHas('category_translations', ['category_id' => $id, 'locale' => 'ar']);

        $this->putJson("/api/categories/{$id}", [
            'translations' => $this->translations('Updated'),
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_student_can_list_but_cannot_manage_categories(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/categories')->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/categories', ['translations' => $this->translations()])
            ->assertForbidden()->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_guest_cannot_list_categories(): void
    {
        $this->getJson('/api/categories')->assertUnauthorized();
    }

    public function test_admin_cannot_delete_category_with_courses(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $category = Category::create();
        $category->translations()->createMany($this->translations());
        Course::factory()->create(['category_id' => $category->id]);

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(400)->assertJsonPath('code', 'CATEGORY_HAS_COURSES');
    }

    public function test_duplicate_locale_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/categories', [
            'translations' => [
                ['locale' => 'ar', 'title' => 'أ'],
                ['locale' => 'ar', 'title' => 'ب'],
            ],
        ])->assertStatus(422)->assertJsonPath('code', 'DUPLICATE_LOCALE');
    }

    public function test_admin_can_view_category_with_translations(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $id = $this->postJson('/api/categories', [
            'translations' => $this->translations('Design'),
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/categories/{$id}")
            ->assertOk()
            ->assertJsonPath('data.translations.ar.title', 'Design عربي')
            ->assertJsonPath('data.translations.en.title', 'Design EN');
    }
}
