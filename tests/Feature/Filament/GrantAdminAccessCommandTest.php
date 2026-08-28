<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin access can be granted to an existing user from artisan', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    $this->artisan('admin:grant', ['email' => $user->email])
        ->expectsOutput("Admin access granted to {$user->email}.")
        ->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeTrue();
});

test('admin access cannot be granted to a missing user', function () {
    $this->artisan('admin:grant', ['email' => 'missing@example.com'])
        ->expectsOutput('User with email missing@example.com was not found.')
        ->assertFailed();
});
