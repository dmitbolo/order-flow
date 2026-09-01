<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test2@example.com',
        'password' => Hash::make('password123'),
    ]);
});

test('user can log in with valid credentials', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'test2@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'access_token',
            'token_type',
        ]);
});

test('login does not reveal whether an email exists', function () {
    $wrongPasswordResponse = $this->postJson('/api/v1/login', [
        'email' => 'test2@example.com',
        'password' => 'wrong-password',
    ]);
    $missingEmailResponse = $this->postJson('/api/v1/login', [
        'email' => 'missing@example.com',
        'password' => 'wrong-password',
    ]);

    $wrongPasswordResponse->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
    $missingEmailResponse->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    expect($missingEmailResponse->json())->toBe($wrongPasswordResponse->json());
});

test('login validates empty credentials', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => '',
        'password' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('authenticated user can view their profile', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'created_at' => $this->user->created_at->toIso8601String(),
            ],
        ]);
});

test('guest cannot view a profile without a token', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized()
        ->assertJsonPath('error_code', 'UNAUTHENTICATED');
});

test('authenticated user can log out', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/logout');

    $response->assertOk()
        ->assertJson([
            'message' => 'Успешный выход из системы',
        ]);
});

test('a token issued at login grants access and is revoked at logout', function () {
    $token = $this->postJson('/api/v1/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ])->json('access_token');

    $this->withToken($token)->getJson('/api/v1/me')->assertOk();
    $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

    expect($this->user->tokens()->count())->toBe(0);
});

test('a guest cannot log out', function () {
    $this->postJson('/api/v1/logout')
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'UNAUTHENTICATED');
});
