<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Actions\Stock\AdjustStockAction;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehousesRelationManager extends RelationManager
{
    protected static string $relationship = 'warehouses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('price')
                    ->label('Цена (в копейках/центах)')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Склад')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('Код склада')
                    ->badge(),
                TextColumn::make('pivot.price')
                    ->label('Цена')
                    ->money('RUB', divideBy: 100) // В зависимости от нужной валюты
                    ->sortable(),
                TextColumn::make('pivot.stock_quantity')
                    ->label('Остаток')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('price')
                            ->label('Цена (в копейках)')
                            ->numeric()
                            ->required(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('adjustStock')
                    ->label('Скорректировать остаток')
                    ->icon('heroicon-o-arrows-right-left')
                    ->form([
                        TextInput::make('quantity_change')
                            ->label('Изменение остатка')
                            ->helperText('Положительное число увеличит остаток, отрицательное — уменьшит.')
                            ->integer()
                            ->required()
                            ->notIn([0]),
                    ])
                    ->action(function (Warehouse $record, array $data): void {
                        $user = auth()->user();

                        app(AdjustStockAction::class)->execute(
                            warehouse: $record,
                            productId: $this->getOwnerRecord()->id,
                            quantityChange: (int) $data['quantity_change'],
                            actor: $user instanceof User ? $user : null,
                        );
                    }),
            ]);
    }
}
