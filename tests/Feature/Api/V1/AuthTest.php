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

test('пользователь может успешно авторизоваться с правильными данными', function () {
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

test('возвращается ошибка валидации при неверном пароле', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'test2@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('возвращается ошибка валидации при передаче пустых полей', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => '',
        'password' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('авторизованный пользователь может получить информацию о себе', function () {
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

test('гость не может получить доступ к /me без токена', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
});

test('авторизованный пользователь может выйти из системы', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/logout');

    $response->assertOk()
        ->assertJson([
            'message' => 'Успешный выход из системы',
        ]);

    expect($this->user->tokens()->count())->toBe(0);
});

test('login does not reveal whether an email exists', function () {
    $this->postJson('/api/v1/login', [
        'email' => 'missing@example.com',
        'password' => 'password123',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
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
    $this->postJson('/api/v1/logout')->assertUnauthorized();
});
