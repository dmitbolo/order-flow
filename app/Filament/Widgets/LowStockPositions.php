<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\WarehouseProduct;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockPositions extends TableWidget
{
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $threshold = max(0, (int) config('inventory.low_stock_threshold', 10));

        return $table
            ->heading('Критические остатки')
            ->description("Позиции с остатком не более {$threshold} единиц")
            ->query(
                WarehouseProduct::query()
                    ->with(['product', 'warehouse'])
                    ->whereRelation('product', 'is_active', true)
                    ->whereRelation('warehouse', 'is_active', true)
                    ->where('stock_quantity', '<=', $threshold)
                    ->orderBy('stock_quantity')
                    ->orderBy('id')
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label('Товар')
                    ->limit(28),
                TextColumn::make('product.sku')
                    ->label('SKU'),
                TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->limit(22),
                TextColumn::make('stock_quantity')
                    ->label('Остаток')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'warning')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB', divideBy: 100),
            ])
            ->recordUrl(
                fn (WarehouseProduct $record): string => ProductResource::getUrl(
                    'edit',
                    ['record' => $record->product_id],
                ),
            )
            ->paginated(false)
            ->emptyStateHeading('Критических остатков нет');
    }
}
