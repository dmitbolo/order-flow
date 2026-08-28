<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $completedTodayQuery = Order::query()
            ->where('status', OrderStatus::Completed)
            ->whereDate('updated_at', today());

        $completedTodayCount = (clone $completedTodayQuery)->count();
        $completedTodayAmount = (int) (clone $completedTodayQuery)->sum('total_amount');
        $pendingOrdersUrl = $this->getOrdersUrl(OrderStatus::Pending);
        $processingOrdersUrl = $this->getOrdersUrl(OrderStatus::Processing);
        $completedOrdersUrl = $this->getOrdersUrl(OrderStatus::Completed);

        return [
            Stat::make(
                'Ожидают обработки',
                Order::query()->where('status', OrderStatus::Pending)->count(),
            )
                ->description('Новые заказы')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url($pendingOrdersUrl),

            Stat::make(
                'В обработке',
                Order::query()->where('status', OrderStatus::Processing)->count(),
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
