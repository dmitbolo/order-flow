<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOrders extends TableWidget
{
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Последние заказы')
            ->query(
                Order::query()
                    ->with(['user', 'warehouse'])
                    ->latest()
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('id')
                    ->label('Заказ')
                    ->prefix('#'),
                TextColumn::make('user.name')
                    ->label('Клиент')
                    ->limit(24),
                TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->limit(20),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('RUB', divideBy: 100),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime(),
            ])
            ->recordUrl(
                fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]),
            )
            ->paginated(false)
            ->emptyStateHeading('Заказов пока нет');
    }
}
