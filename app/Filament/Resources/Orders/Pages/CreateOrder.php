<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\CreateOrderAction;
use App\DTO\CreateOrderData;
use App\DTO\OrderItemData;
use App\Exceptions\AppException;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use LogicException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            $customer = User::query()->findOrFail((int) $data['user_id']);
            $actor = Filament::auth()->user();

            if (! $actor instanceof User) {
                throw new LogicException('The authenticated Filament user is invalid.');
            }

            $rawItemRows = $data['items'] ?? [];

            if (! is_array($rawItemRows)) {
                throw new LogicException('The order items state is invalid.');
            }

            /** @var list<array{product_id: int|string, quantity: int|string}> $itemRows */
            $itemRows = array_values($rawItemRows);

            $order = app(CreateOrderAction::class)->execute(
                user: $customer,
                data: new CreateOrderData(
                    warehouseId: (int) $data['warehouse_id'],
                    items: array_map(
                        static fn (array $item): OrderItemData => OrderItemData::fromArray($item),
                        $itemRows,
                    ),
                    notes: isset($data['notes']) ? (string) $data['notes'] : null,
                ),
                actor: $actor,
            );

            return $order;
        } catch (AppException $exception) {
            Notification::make()
                ->danger()
                ->title('Не удалось создать заказ')
                ->body($exception->errorMessage)
                ->send();

            throw new Halt;
        } catch (ModelNotFoundException) {
            Notification::make()
                ->danger()
                ->title('Не удалось создать заказ')
                ->body('Клиент или активный склад больше не существует.')
                ->send();

            throw new Halt;
        } catch (InvalidArgumentException $exception) {
            Notification::make()
                ->danger()
                ->title('Не удалось создать заказ')
                ->body($exception->getMessage())
                ->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Заказ создан, остатки списаны';
    }
}
