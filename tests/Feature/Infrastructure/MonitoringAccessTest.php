<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Horizon\Horizon;
use Laravel\Telescope\Telescope;

uses(RefreshDatabase::class);

test('monitoring authorization callbacks allow only administrators', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $userRequest = Request::create('/monitoring');
    $userRequest->setUserResolver(fn (): User => $user);
    $adminRequest = Request::create('/monitoring');
    $adminRequest->setUserResolver(fn (): User => $admin);

    $this->actingAs($user);
    expect(Horizon::check($userRequest))->toBeFalse()
        ->and(Telescope::check($userRequest))->toBeFalse();

    $this->actingAs($admin);
    expect(Horizon::check($adminRequest))->toBeTrue()
        ->and(Telescope::check($adminRequest))->toBeTrue();
});
