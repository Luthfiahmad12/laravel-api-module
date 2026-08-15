<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(TestCase::class);
uses(RefreshDatabase::class);

test('a user can register', function () {
    postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
        ])
        ->assertJsonPath('data.email', 'john@example.com');

    expect(User::where('email', 'john@example.com')->exists())->toBeTrue();
});

test('registration validates required fields', function () {
    postJson('/api/auth/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration rejects a duplicate email', function () {
    $existing = User::factory()->create();

    postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => $existing->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('a user can login with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ]);
});

test('login fails with invalid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('an authenticated user can retrieve their profile', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->getJson('/api/auth/me')
        ->assertSuccessful()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', $user->email);
});

test('a guest cannot retrieve the profile', function () {
    getJson('/api/auth/me')->assertUnauthorized();
});

test('an authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/logout')
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('a guest cannot logout', function () {
    postJson('/api/auth/logout')->assertUnauthorized();
});
