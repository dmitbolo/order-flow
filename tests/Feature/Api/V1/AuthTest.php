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
