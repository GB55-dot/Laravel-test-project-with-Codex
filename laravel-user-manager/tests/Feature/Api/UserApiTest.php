<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_user_api(): void
    {
        $this->getJson('/api/users')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_authenticated_user_can_receive_a_paginated_list(): void
    {
        $actor = $this->authenticate();
        User::factory()->count(12)->create();

        $this->getJson('/api/users?per_page=5&page=1')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 13)
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonFragment(['email' => User::query()->latest('id')->value('email')]);

        $this->assertDatabaseHas('users', ['id' => $actor->id]);
    }

    public function test_first_party_session_can_authenticate_an_ajax_api_request(): void
    {
        $actor = User::factory()->create();

        $this->actingAs($actor)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_authenticated_user_can_create_a_user(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/users', [
            'name' => 'Ada Lovelace',
            'email' => 'ADA@example.test',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ada Lovelace')
            ->assertJsonPath('data.email', 'ada@example.test')
            ->assertJsonMissingPath('data.password');

        $user = User::query()->where('email', 'ada@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('Strong-password-123', $user->password));
    }

    public function test_store_validation_returns_actionable_errors(): void
    {
        $this->authenticate();

        $this->postJson('/api/users', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_authenticated_user_can_view_one_user(): void
    {
        $this->authenticate();
        $target = User::factory()->create();

        $this->getJson("/api/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.email', $target->email);
    }

    public function test_missing_user_has_a_consistent_json_404(): void
    {
        $this->authenticate();

        $this->getJson('/api/users/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Користувача не знайдено.']);
    }

    public function test_authenticated_user_can_update_a_user(): void
    {
        $this->authenticate();
        $target = User::factory()->create([
            'email' => 'before@example.test',
            'password' => 'old-password',
        ]);

        $this->putJson("/api/users/{$target->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@example.test',
            'password' => 'New-password-123',
            'password_confirmation' => 'New-password-123',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@example.test');

        $target->refresh();

        $this->assertTrue(Hash::check('New-password-123', $target->password));
    }

    public function test_password_is_preserved_when_update_omits_it(): void
    {
        $this->authenticate();
        $target = User::factory()->create(['password' => 'old-password']);
        $originalHash = $target->password;

        $this->putJson("/api/users/{$target->id}", [
            'name' => 'Name only',
            'email' => $target->email,
        ])->assertOk();

        $this->assertSame($originalHash, $target->refresh()->password);
    }

    public function test_authenticated_user_can_delete_a_user(): void
    {
        $this->authenticate();
        $target = User::factory()->create();

        $this->deleteJson("/api/users/{$target->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_create_invalidates_a_previously_cached_list(): void
    {
        $this->authenticate();
        User::factory()->create();

        $this->getJson('/api/users')->assertJsonPath('meta.total', 2);

        $this->postJson('/api/users', [
            'name' => 'Cache Example',
            'email' => 'cache@example.test',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ])->assertCreated();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonFragment(['email' => 'cache@example.test']);
    }

    private function authenticate(): User
    {
        $actor = User::factory()->create();
        Sanctum::actingAs($actor);

        return $actor;
    }
}
