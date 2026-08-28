<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Заказ')
                    ->schema([
                        Select::make('user_id')
                            ->label('Клиент')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(
                                fn (User $record): string => "{$record->name} ({$record->email})",
                            )
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->required(),

                        Select::make('warehouse_id')
                            ->label('Склад отгрузки')
                            ->options(fn (): array => Warehouse::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('items', []))
                            ->required(),

                        Textarea::make('notes')
                            ->label('Заметки / пожелания')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Состав заказа')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->addActionLabel('Добавить товар')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Товар')
                                    ->options(function (Get $get): array {
                                        $warehouseId = (int) $get('../../warehouse_id');

                                        if ($warehouseId < 1) {
                                            return [];
                                        }

                                        return Product::query()
                                            ->where('is_active', true)
                                            ->whereHas(
                                                'warehouses',
                                                fn (Builder $query): Builder => $query
                                                    ->where('warehouses.id', $warehouseId)
                                                    ->where('warehouse_product.stock_quantity', '>', 0),
                                            )
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all();
                                    })
                                    ->disabled(fn (Get $get): bool => ! $get('../../warehouse_id'))
                                    ->searchable()
                                    ->preload()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->required(),
                    ]),
            ]);
    }
}
