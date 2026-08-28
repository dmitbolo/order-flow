<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название товара')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('sku')
                            ->label('Артикул (SKU)')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Textarea::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->helperText('Неактивные товары скрыты в API и недоступны для новых заказов.')
                            ->default(true),
                    ])->columns(2),
            ]);
    }
}
