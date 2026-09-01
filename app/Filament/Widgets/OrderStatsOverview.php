<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class OrderStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $dayStart = today();
        $nextDayStart = $dayStart->copy()->addDay();

        $orderStats = Order::query()
            ->selectRaw(
                <<<'SQL'
                    COUNT(CASE WHEN status = ? THEN 1 END) AS pending_count,
                    COUNT(CASE WHEN status = ? THEN 1 END) AS processing_count,
                    COUNT(CASE WHEN status = ? THEN 1 END) AS completed_today_count,
                    COALESCE(SUM(CASE WHEN status = ? THEN total_amount ELSE 0 END), 0) AS completed_today_amount
                SQL,
                [
                    OrderStatus::Pending->value,
                    OrderStatus::Processing->value,
                    OrderStatus::Completed->value,
                    OrderStatus::Completed->value,
                ],
            )
            ->where(function (Builder $query) use ($dayStart, $nextDayStart): void {
                $query
                    ->whereIn('status', [
                        OrderStatus::Pending->value,
                        OrderStatus::Processing->value,
                    ])
                    ->orWhere(function (Builder $query) use ($dayStart, $nextDayStart): void {
                        $query
                            ->where('status', OrderStatus::Completed->value)
                            ->where('updated_at', '>=', $dayStart)
                            ->where('updated_at', '<', $nextDayStart);
                    });
            })
            ->firstOrFail();

        $pendingCount = (int) $orderStats->getAttribute('pending_count');
        $processingCount = (int) $orderStats->getAttribute('processing_count');
        $completedTodayCount = (int) $orderStats->getAttribute('completed_today_count');
        $completedTodayAmount = (int) $orderStats->getAttribute('completed_today_amount');
        $pendingOrdersUrl = $this->getOrdersUrl(OrderStatus::Pending);
        $processingOrdersUrl = $this->getOrdersUrl(OrderStatus::Processing);
        $completedOrdersUrl = $this->getOrdersUrl(OrderStatus::Completed);

        return [
            Stat::make(
                'Ожидают обработки',
                $pendingCount,
            )
                ->description('Новые заказы')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url($pendingOrdersUrl),

            Stat::make(
                'В обработке',
                $processingCount,
            )
                ->description('Требуют завершения')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->url($processingOrdersUrl),

            Stat::make('Завершены сегодня', $completedTodayCount)
                ->description('По дате последнего изменения')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url($completedOrdersUrl),

            Stat::make('Сумма завершённых сегодня', $this->formatMoney($completedTodayAmount))
                ->description('Только завершённые заказы')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->url($completedOrdersUrl),
        ];
    }

    private function getOrdersUrl(OrderStatus $status): string
    {
        return OrderResource::getUrl('index', [
            'filters' => [
                'status' => [
                    'value' => $status->value,
                ],
            ],
        ]);
    }

    private function formatMoney(int $amount): string
    {
        return number_format($amount / 100, 2, ',', ' ').' ₽';
    }
}
