<?php

use App\Enums\OrderStatus;

test('order status allows only configured transitions', function () {
    $allowedTargets = [
        OrderStatus::Pending->value => [OrderStatus::Processing],
        OrderStatus::Processing->value => [OrderStatus::Completed],
        OrderStatus::Canceled->value => [],
        OrderStatus::Completed->value => [],
    ];

    foreach (OrderStatus::cases() as $status) {
        foreach (OrderStatus::cases() as $target) {
            $expected = in_array($target, $allowedTargets[$status->value], true);

            expect($status->canTransitionTo($target))
                ->toBe($expected, "Unexpected transition from {$status->value} to {$target->value}.");
        }
    }
});
