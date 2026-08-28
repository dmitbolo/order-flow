<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Actions\Stock\AdjustStockAction;
use App\Exceptions\AppException;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class WarehousesRelationManager extends RelationManager
{
    protected static string $relationship = 'warehouses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('price')
                    ->label('Цена (в копейках/центах)')
                    ->integer()
                    ->minValue(0)
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
                    ->recordSelectOptionsQuery(
                        fn (Builder $query): Builder => $query->where('is_active', true),
                    )
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('price')
                            ->label('Цена (в копейках)')
                            ->integer()
                            ->minValue(0)
                            ->required(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('adjustStock')
                    ->label('Скорректировать остаток')
                    ->icon('heroicon-o-arrows-right-left')
                    ->schema([
                        TextInput::make('quantity_change')
                            ->label('Изменение остатка')
                            ->helperText('Положительное число увеличит остаток, отрицательное — уменьшит.')
                            ->integer()
                            ->required()
                            ->notIn([0]),
                        Textarea::make('comment')
                            ->label('Комментарий')
                            ->maxLength(255)
                            ->required(),
                    ])
                    ->action(function (Warehouse $record, array $data): void {
                        $user = Filament::auth()->user();

                        if (! $user instanceof User) {
                            throw new LogicException('The authenticated Filament user is invalid.');
                        }

                        /** @var Product $product */
                        $product = $this->getOwnerRecord();

                        try {
                            app(AdjustStockAction::class)->execute(
                                warehouse: $record,
                                productId: $product->id,
                                quantityChange: (int) $data['quantity_change'],
                                actor: $user,
                                comment: (string) $data['comment'],
                            );

                            Notification::make()
                                ->success()
                                ->title('Остаток скорректирован')
                                ->send();
                        } catch (AppException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Не удалось скорректировать остаток')
                                ->body($exception->errorMessage)
                                ->send();
                        }
                    }),
            ]);
    }
}
