<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название склада')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('code')
                    ->label('Код склада')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('address')
                    ->label('Адрес')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->helperText('Неактивный склад скрыт в API и недоступен для новых заказов.')
                    ->default(true),
            ]);
    }
}
