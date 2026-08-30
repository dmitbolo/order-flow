<?php

use Illuminate\Console\Scheduling\Schedule;

test('horizon supervisors have a finite retry fallback', function () {
    expect(config('horizon.defaults.supervisor-inventory.tries'))->toBe(3)
        ->and(config('horizon.defaults.supervisor-notifications.tries'))->toBe(3);
});

test('queue monitoring maintenance is scheduled', function () {
    $events = collect(app(Schedule::class)->events());
    $horizonSnapshot = $events->first(
        fn ($event): bool => str_contains((string) $event->command, 'horizon:snapshot'),
    );
    $telescopePrune = $events->first(
        fn ($event): bool => str_contains((string) $event->command, 'telescope:prune --hours=48'),
    );

    expect($horizonSnapshot)->not->toBeNull()
        ->and($horizonSnapshot->expression)->toBe('*/5 * * * *')
        ->and($telescopePrune)->not->toBeNull()
        ->and($telescopePrune->expression)->toBe('0 0 * * *');
});
