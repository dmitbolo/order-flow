<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\OrderItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Заказ')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Клиент'),
                        TextEntry::make('warehouse.name')
                            ->label('Склад'),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge(),
                        TextEntry::make('total_amount')
                            ->label('Итоговая сумма')
                            ->money('RUB', divideBy: 100),
                        TextEntry::make('notes')
                            ->label('Заметки')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Создан')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Обновлён')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Состав заказа')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Товар'),
                                TextEntry::make('quantity')
                                    ->label('Количество')
                                    ->numeric(),
                                TextEntry::make('price')
                                    ->label('Цена')
                                    ->money('RUB', divideBy: 100),
                                TextEntry::make('line_total')
                                    ->label('Сумма')
                                    ->state(fn (OrderItem $record): int => $record->price * $record->quantity)
                                    ->money('RUB', divideBy: 100),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Движения остатков')
                    ->schema([
                        RepeatableEntry::make('stockMovements')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Дата')
                                    ->dateTime(),
                                TextEntry::make('product.name')
                                    ->label('Товар'),
                                TextEntry::make('type')
                                    ->label('Операция')
                                    ->badge(),
                                TextEntry::make('quantity_change')
                                    ->label('Изменение')
                                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "+{$state}" : (string) $state),
                                TextEntry::make('actor.name')
                                    ->label('Исполнитель')
                                    ->placeholder('Система'),
                                TextEntry::make('comment')
                                    ->label('Комментарий')
                                    ->placeholder('-'),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible(),
            ]);
    }
}
