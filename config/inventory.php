<?php

return [
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 10),
    'low_stock_notification_cooldown_seconds' => (int) env('LOW_STOCK_NOTIFICATION_COOLDOWN_SECONDS', 86400),
];
