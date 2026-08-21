<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('Операция')
                    ->formatStateUsing(fn (StockMovementType $state): string => $state->label())
                    ->badge(),
                TextColumn::make('quantity_change')
                    ->label('Изменение')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('quantity_before')
                    ->label('Было')
                    ->sortable(),
                TextColumn::make('quantity_after')
                    ->label('Стало')
                    ->sortable(),
                TextColumn::make('order_id')
                    ->label('Заказ')
                    ->prefix('#')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->relationship('warehouse', 'name'),
                SelectFilter::make('product_id')
                    ->label('Товар')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->label('Операция')
                    ->options(collect(StockMovementType::cases())
                        ->mapWithKeys(fn (StockMovementType $type) => [$type->value => $type->label()])
                        ->all()),
                Filter::make('created_at')
                    ->label('Период')
                    ->schema([
                        DatePicker::make('from')->label('С'),
                        DatePicker::make('until')->label('По'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ]);
    }
}
