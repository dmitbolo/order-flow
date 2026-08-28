<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Клиент')
                    ->searchable(),

                TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),

                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(OrderStatus::class),

                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->relationship('warehouse', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
