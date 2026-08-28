<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\StockMovement;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentStockMovements extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Последние движения остатков')
            ->query(
                StockMovement::query()
                    ->with(['warehouse', 'product', 'actor'])
                    ->latest()
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime(),
                TextColumn::make('warehouse.name')
                    ->label('Склад'),
                TextColumn::make('product.name')
                    ->label('Товар'),
                TextColumn::make('type')
                    ->label('Операция')
                    ->badge(),
                TextColumn::make('quantity_change')
                    ->label('Изменение')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('actor.name')
                    ->label('Исполнитель')
                    ->placeholder('Система'),
                TextColumn::make('order_id')
                    ->label('Заказ')
                    ->prefix('#')
                    ->placeholder('-'),
            ])
            ->recordUrl(fn (StockMovement $record): ?string => $record->order_id
                ? OrderResource::getUrl('view', ['record' => $record->order_id])
                : null)
            ->paginated(false)
            ->emptyStateHeading('Движений остатков пока нет');
    }
}
