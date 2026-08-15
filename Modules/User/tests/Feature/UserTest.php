<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(TestCase::class);
uses(RefreshDatabase::class);

test('guest cannot access the users endpoint', function () {
    getJson('/api/users')->assertUnauthorized();
});

test('can retrieve a paginated list of users', function () {
    $user = User::factory()->create();
    User::factory()->count(3)->create();

    actingAs($user, 'sanctum')
        ->getJson('/api/users')
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ],
        ])
        ->assertJsonPath('success', true);
});

test('can retrieve a single user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    actingAs($user, 'sanctum')
        ->getJson("/api/users/{$target->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.email', $target->email);
});

test('returns not found for a missing user', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->getJson('/api/users/999999')
        ->assertNotFound();
});

test('can create a user', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->postJson('/api/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'jane@example.com');

    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('validates required fields when creating a user', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->postJson('/api/users', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('rejects duplicate email when creating a user', function () {
    $user = User::factory()->create();
    $existing = User::factory()->create();

    actingAs($user, 'sanctum')
        ->postJson('/api/users', [
            'name' => 'Someone',
            'email' => $existing->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('can update a user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    actingAs($user, 'sanctum')
        ->putJson("/api/users/{$target->id}", [
            'name' => 'Updated Name',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    expect($target->fresh()->name)->toBe('Updated Name');
});

test('can delete a user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    actingAs($user, 'sanctum')
        ->deleteJson("/api/users/{$target->id}")
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(User::find($target->id))->toBeNull();
});
