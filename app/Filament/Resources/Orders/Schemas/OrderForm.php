<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Клиент')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                Select::make('warehouse_id')
                    ->label('Склад отгрузки')
                    ->relationship('warehouse', 'name')
                    ->required(),

                Select::make('status')
                    ->label('Статус заказа')
                    ->options([
                        'pending' => 'Pending (Ожидает)',
                        'processing' => 'Processing (В обработке)',
                        'completed' => 'Completed (Завершен)',
                        'cancelled' => 'Cancelled (Отменен)',
                    ])
                    ->default('pending')
                    ->required(),

                TextInput::make('total_amount')
                    ->label('Итоговая сумма (в копейках)')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Textarea::make('notes')
                    ->label('Заметки / Пожелания')
                    ->columnSpanFull(),

                // Позволяет добавлять товары напрямую внутри формы заказа
                Repeater::make('items')
                    ->label('Состав заказа')
                    ->relationship('items')
                    ->schema([
                        Select::make('product_id')
                            ->label('Товар')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Количество')
                            ->numeric()
                            ->default(1)
                            ->required(),

                        TextInput::make('price')
                            ->label('Цена (коп.)')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
