<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\TransitionOrderStatusAction;
use App\Enums\OrderStatus;
use App\Exceptions\AppException;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use LogicException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startProcessing')
                ->label('Взять в обработку')
                ->icon('heroicon-o-play')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Pending)
                ->action(fn (Order $record) => $this->transitionOrder($record, OrderStatus::Processing)),

            Action::make('complete')
                ->label('Завершить')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Processing)
                ->action(fn (Order $record) => $this->transitionOrder($record, OrderStatus::Completed)),

            Action::make('cancel')
                ->label('Отменить заказ')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Остатки товаров будут возвращены на склад.')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Pending)
                ->action(fn (Order $record) => $this->cancelOrder($record)),
        ];
    }

    private function transitionOrder(Order $order, OrderStatus $status): void
    {
        try {
            $updatedOrder = app(TransitionOrderStatusAction::class)->execute($order, $status);
            $this->useUpdatedRecord($updatedOrder);

            Notification::make()
                ->success()
                ->title('Статус заказа обновлён')
                ->send();
        } catch (AppException $exception) {
            $this->notifyFailure($exception);
        }
    }

    private function cancelOrder(Order $order): void
    {
        $actor = Filament::auth()->user();

        if (! $actor instanceof User) {
            throw new LogicException('The authenticated Filament user is invalid.');
        }

        try {
            $updatedOrder = app(CancelOrderAction::class)->execute($order, $actor);
            $this->useUpdatedRecord($updatedOrder);

            Notification::make()
                ->success()
                ->title('Заказ отменён, остатки возвращены')
                ->send();
        } catch (AppException $exception) {
            $this->notifyFailure($exception);
        }
    }

    private function useUpdatedRecord(Order $order): void
    {
        $this->record = $order->loadMissing(OrderResource::getRecordEagerLoads());
        $this->getSchema('infolist')->record($this->record);
    }

    private function notifyFailure(AppException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Операция не выполнена')
            ->body($exception->errorMessage)
            ->send();
    }
}
