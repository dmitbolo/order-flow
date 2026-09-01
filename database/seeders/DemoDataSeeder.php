<?php

namespace Database\Seeders;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\CreateOrderAction;
use App\Actions\Orders\TransitionOrderStatusAction;
use App\Actions\Stock\AdjustStockAction;
use App\DTO\CreateOrderData;
use App\DTO\OrderItemData;
use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use LogicException;

class DemoDataSeeder extends Seeder
{
    private const int DEMO_ORDER_COUNT = 12;

    private const string DEMO_ORDER_PATTERN = 'DEMO OF-%';

    private const string INITIAL_BALANCE_COMMENT = 'Demo dataset · начальный остаток';

    private const string INVENTORY_COMMENT = 'Инвентаризация: обнаружено 5 единиц';

    /** @var array<string, array{name: string, address: string, is_active: bool}> */
    private const array WAREHOUSES = [
        'MSK-01' => [
            'name' => 'Москва — Центральный',
            'address' => 'Москва, ул. Складочная, 1',
            'is_active' => true,
        ],
        'SPB-01' => [
            'name' => 'Санкт-Петербург — Север',
            'address' => 'Санкт-Петербург, пр. Энергетиков, 21',
            'is_active' => true,
        ],
        'KZN-01' => [
            'name' => 'Казань — Резерв',
            'address' => 'Казань, Тихорецкая ул., 7',
            'is_active' => false,
        ],
    ];

    /** @var array<string, array{name: string, description: string, is_active: bool}> */
    private const array PRODUCTS = [
        'SCN-ZEB-2278' => [
            'name' => 'Сканер штрихкодов Zebra DS2278',
            'description' => 'Беспроводной 2D-сканер для кассовой зоны и склада.',
            'is_active' => true,
        ],
        'PRN-XP-365B' => [
            'name' => 'Принтер этикеток Xprinter XP-365B',
            'description' => 'Термопринтер этикеток для маркировки товара.',
            'is_active' => true,
        ],
        'POS-ATOL-S10' => [
            'name' => 'POS-терминал АТОЛ Sigma 10',
            'description' => 'Сенсорный терминал для автоматизации розничной точки.',
            'is_active' => true,
        ],
        'DRW-ATOL-410' => [
            'name' => 'Денежный ящик АТОЛ CD-410',
            'description' => 'Металлический денежный ящик с электромеханическим замком.',
            'is_active' => true,
        ],
        'LBL-58X40-700' => [
            'name' => 'Термоэтикетки 58×40, 700 шт.',
            'description' => 'Рулон самоклеящихся этикеток для термопечати.',
            'is_active' => true,
        ],
        'RFID-USB-01' => [
            'name' => 'RFID-считыватель USB',
            'description' => 'Настольный считыватель RFID-меток для учёта товара.',
            'is_active' => true,
        ],
        'SCL-MER-326' => [
            'name' => 'Торговые весы M-ER 326',
            'description' => 'Электронные торговые весы с расчётом стоимости.',
            'is_active' => true,
        ],
        'SCN-HW-1250G' => [
            'name' => 'Сканер Honeywell Voyager 1250g',
            'description' => 'Снятая с продажи модель проводного сканера.',
            'is_active' => false,
        ],
    ];

    /** @var list<array{warehouse: string, product: string, price: int, stock: int}> */
    private const array STOCK_POSITIONS = [
        ['warehouse' => 'MSK-01', 'product' => 'SCN-ZEB-2278', 'price' => 1_899_000, 'stock' => 24],
        ['warehouse' => 'MSK-01', 'product' => 'PRN-XP-365B', 'price' => 1_249_000, 'stock' => 15],
        ['warehouse' => 'MSK-01', 'product' => 'POS-ATOL-S10', 'price' => 3_999_000, 'stock' => 8],
        ['warehouse' => 'MSK-01', 'product' => 'DRW-ATOL-410', 'price' => 549_000, 'stock' => 32],
        ['warehouse' => 'MSK-01', 'product' => 'LBL-58X40-700', 'price' => 39_000, 'stock' => 120],
        ['warehouse' => 'MSK-01', 'product' => 'RFID-USB-01', 'price' => 799_000, 'stock' => 9],
        ['warehouse' => 'MSK-01', 'product' => 'SCL-MER-326', 'price' => 899_000, 'stock' => 11],
        ['warehouse' => 'MSK-01', 'product' => 'SCN-HW-1250G', 'price' => 1_299_000, 'stock' => 4],
        ['warehouse' => 'SPB-01', 'product' => 'SCN-ZEB-2278', 'price' => 1_949_000, 'stock' => 12],
        ['warehouse' => 'SPB-01', 'product' => 'PRN-XP-365B', 'price' => 1_299_000, 'stock' => 7],
        ['warehouse' => 'SPB-01', 'product' => 'DRW-ATOL-410', 'price' => 579_000, 'stock' => 9],
        ['warehouse' => 'SPB-01', 'product' => 'LBL-58X40-700', 'price' => 42_000, 'stock' => 80],
        ['warehouse' => 'SPB-01', 'product' => 'SCL-MER-326', 'price' => 929_000, 'stock' => 5],
        ['warehouse' => 'KZN-01', 'product' => 'SCN-ZEB-2278', 'price' => 1_899_000, 'stock' => 6],
        ['warehouse' => 'KZN-01', 'product' => 'PRN-XP-365B', 'price' => 1_249_000, 'stock' => 4],
        ['warehouse' => 'KZN-01', 'product' => 'RFID-USB-01', 'price' => 799_000, 'stock' => 0],
    ];

    public function __construct(
        private readonly CreateOrderAction $createOrder,
        private readonly CancelOrderAction $cancelOrder,
        private readonly TransitionOrderStatusAction $transitionOrder,
        private readonly AdjustStockAction $adjustStock,
    ) {}

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Demo data cannot be seeded in production.');
        }

        Event::fakeFor(function (): void {
            DB::transaction(function (): void {
                if ($this->demoDatasetExists()) {
                    $this->command?->info('Demo dataset already exists; no records were changed.');

                    return;
                }

                $this->ensureDemoCatalogIsAbsent();

                $anchor = now();
                $users = $this->seedUsers();
                $warehouses = $this->seedWarehouses();
                $products = $this->seedProducts();

                $this->seedInitialStock($warehouses, $products, $anchor);
                $this->seedOrders($users, $warehouses, $products, $anchor);
                $this->seedInventoryAdjustment(
                    $users['admin'],
                    $warehouses['SPB-01'],
                    $products['PRN-XP-365B'],
                    $this->todayAt($anchor, 98),
                );
            });
        });
    }

    private function demoDatasetExists(): bool
    {
        $demoOrderCount = Order::query()
            ->where('notes', 'like', self::DEMO_ORDER_PATTERN)
            ->count();

        if ($demoOrderCount === 0) {
            return false;
        }

        if ($demoOrderCount !== self::DEMO_ORDER_COUNT) {
            throw new LogicException(
                'A partial demo dataset already exists. Run "php artisan migrate:fresh --seed" to rebuild it safely.',
            );
        }

        return true;
    }

    private function ensureDemoCatalogIsAbsent(): void
    {
        $warehouseExists = Warehouse::query()
            ->whereIn('code', array_keys(self::WAREHOUSES))
            ->exists();
        $productExists = Product::query()
            ->whereIn('sku', array_keys(self::PRODUCTS))
            ->exists();

        if ($warehouseExists || $productExists) {
            throw new LogicException(
                'A partial demo dataset already exists. Run "php artisan migrate:fresh --seed" to rebuild it safely.',
            );
        }
    }

    /** @return array<string, User> */
    private function seedUsers(): array
    {
        $definitions = [
            'admin' => [
                'name' => 'Оператор Order Flow',
                'email' => 'test@example.com',
                'is_admin' => true,
            ],
            'alexey' => [
                'name' => 'Алексей Морозов',
                'email' => 'alexey@order-flow.local',
                'is_admin' => false,
            ],
            'maria' => [
                'name' => 'Мария Соколова',
                'email' => 'maria@order-flow.local',
                'is_admin' => false,
            ],
            'sever_torg' => [
                'name' => 'ООО «Север Торг»',
                'email' => 'sales@severtorg.local',
                'is_admin' => false,
            ],
        ];
        $users = [];

        foreach ($definitions as $key => $definition) {
            $user = User::query()->updateOrCreate(
                ['email' => $definition['email']],
                ['name' => $definition['name'], 'password' => 'password'],
            );
            $user->forceFill([
                'email_verified_at' => now(),
                'is_admin' => $definition['is_admin'],
            ])->saveQuietly();

            $users[$key] = $user;
        }

        return $users;
    }

    /** @return array<string, Warehouse> */
    private function seedWarehouses(): array
    {
        $warehouses = [];

        foreach (self::WAREHOUSES as $code => $definition) {
            $warehouses[$code] = Warehouse::query()->create([
                'code' => $code,
                ...$definition,
            ]);
        }

        return $warehouses;
    }

    /** @return array<string, Product> */
    private function seedProducts(): array
    {
        $products = [];

        foreach (self::PRODUCTS as $sku => $definition) {
            $products[$sku] = Product::query()->create([
                'sku' => $sku,
                ...$definition,
            ]);
        }

        return $products;
    }

    /**
     * @param  array<string, Warehouse>  $warehouses
     * @param  array<string, Product>  $products
     */
    private function seedInitialStock(array $warehouses, array $products, Carbon $anchor): void
    {
        $createdAt = $anchor->copy()->subDays(10)->startOfHour();

        foreach (self::STOCK_POSITIONS as $definition) {
            $warehouse = $warehouses[$definition['warehouse']];
            $product = $products[$definition['product']];

            DB::table('warehouse_product')->insert([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'price' => $definition['price'],
                'stock_quantity' => $definition['stock'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($definition['stock'] === 0) {
                continue;
            }

            $movement = StockMovement::query()->create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'type' => StockMovementType::InitialBalance,
                'quantity_change' => $definition['stock'],
                'quantity_before' => 0,
                'quantity_after' => $definition['stock'],
                'comment' => self::INITIAL_BALANCE_COMMENT,
            ]);

            DB::table('stock_movements')
                ->where('id', $movement->id)
                ->update(['created_at' => $createdAt]);
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Warehouse>  $warehouses
     * @param  array<string, Product>  $products
     */
    private function seedOrders(array $users, array $warehouses, array $products, Carbon $anchor): void
    {
        /** @var list<array{order: Order, created_at: Carbon, updated_at: Carbon}> $deferredCancellations */
        $deferredCancellations = [];

        foreach ($this->orderScenarios($anchor) as $scenario) {
            $items = [];

            foreach ($scenario['items'] as $sku => $quantity) {
                $items[] = new OrderItemData(
                    productId: $products[$sku]->id,
                    quantity: $quantity,
                );
            }

            $order = $this->createOrder->execute(
                user: $users[$scenario['user']],
                data: new CreateOrderData(
                    warehouseId: $warehouses[$scenario['warehouse']]->id,
                    items: $items,
                    notes: $scenario['notes'],
                ),
            );

            if ($scenario['defer_cancellation'] ?? false) {
                $this->setOrderTimeline(
                    order: $order,
                    createdAt: $scenario['created_at'],
                    updatedAt: $scenario['created_at'],
                );
                $deferredCancellations[] = [
                    'order' => $order,
                    'created_at' => $scenario['created_at'],
                    'updated_at' => $scenario['updated_at'],
                ];

                continue;
            }

            $order = $this->applyFinalStatus($order, $scenario['status'], $users['admin']);

            $this->setOrderTimeline(
                order: $order,
                createdAt: $scenario['created_at'],
                updatedAt: $scenario['updated_at'],
            );
        }

        foreach ($deferredCancellations as $deferred) {
            $order = $this->cancelOrder->execute($deferred['order'], $users['admin']);

            $this->setOrderTimeline(
                order: $order,
                createdAt: $deferred['created_at'],
                updatedAt: $deferred['updated_at'],
            );
        }
    }

    private function applyFinalStatus(Order $order, OrderStatus $status, User $admin): Order
    {
        return match ($status) {
            OrderStatus::Pending => $order,
            OrderStatus::Processing => $this->transitionOrder->execute($order, OrderStatus::Processing),
            OrderStatus::Canceled => $this->cancelOrder->execute($order, $admin),
            OrderStatus::Completed => $this->completeOrder($order),
        };
    }

    private function completeOrder(Order $order): Order
    {
        $order = $this->transitionOrder->execute($order, OrderStatus::Processing);

        return $this->transitionOrder->execute($order, OrderStatus::Completed);
    }

    private function setOrderTimeline(Order $order, Carbon $createdAt, Carbon $updatedAt): void
    {
        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        DB::table('order_items')
            ->where('order_id', $order->id)
            ->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        DB::table('stock_movements')
            ->where('order_id', $order->id)
            ->where('type', StockMovementType::OrderCreated->value)
            ->update(['created_at' => $createdAt]);
        DB::table('stock_movements')
            ->where('order_id', $order->id)
            ->where('type', StockMovementType::OrderCanceled->value)
            ->update(['created_at' => $updatedAt]);
    }

    private function seedInventoryAdjustment(
        User $admin,
        Warehouse $warehouse,
        Product $product,
        Carbon $createdAt,
    ): void {
        $this->adjustStock->execute(
            warehouse: $warehouse,
            productId: $product->id,
            quantityChange: 5,
            actor: $admin,
            comment: self::INVENTORY_COMMENT,
        );

        $movementId = StockMovement::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->where('actor_id', $admin->id)
            ->where('type', StockMovementType::ManualAdjustment)
            ->where('comment', self::INVENTORY_COMMENT)
            ->latest('id')
            ->value('id');

        if ($movementId === null) {
            throw new LogicException('The demo inventory adjustment was not recorded.');
        }

        DB::table('stock_movements')
            ->where('id', $movementId)
            ->update(['created_at' => $createdAt]);
    }

    /**
     * @return list<array{
     *     notes: string,
     *     user: string,
     *     warehouse: string,
     *     status: OrderStatus,
     *     items: array<string, int>,
     *     created_at: Carbon,
     *     updated_at: Carbon,
     *     defer_cancellation?: bool
     * }>
     */
    private function orderScenarios(Carbon $anchor): array
    {
        $today = $anchor->copy()->startOfDay();

        return [
            [
                'notes' => 'DEMO OF-1001 · Открытие новой торговой точки',
                'user' => 'sever_torg',
                'warehouse' => 'SPB-01',
                'status' => OrderStatus::Completed,
                'items' => ['SCN-ZEB-2278' => 2],
                'created_at' => $today->copy()->subDays(9)->addHours(10),
                'updated_at' => $today->copy()->subDays(8)->addHours(14),
            ],
            [
                'notes' => 'DEMO OF-1002 · Оснащение весового отдела',
                'user' => 'alexey',
                'warehouse' => 'MSK-01',
                'status' => OrderStatus::Completed,
                'items' => ['SCL-MER-326' => 2],
                'created_at' => $today->copy()->subDays(7)->addHours(11),
                'updated_at' => $today->copy()->subDays(6)->addHours(15),
            ],
            [
                'notes' => 'DEMO OF-1003 · Отменённая замена кассового узла',
                'user' => 'maria',
                'warehouse' => 'SPB-01',
                'status' => OrderStatus::Canceled,
                'items' => ['PRN-XP-365B' => 1, 'DRW-ATOL-410' => 1],
                'created_at' => $today->copy()->subDays(5)->addHours(9),
                'updated_at' => $today->copy()->subDays(4)->addHours(10),
            ],
            [
                'notes' => 'DEMO OF-1004 · Отмена после сверки спецификации',
                'user' => 'alexey',
                'warehouse' => 'MSK-01',
                'status' => OrderStatus::Canceled,
                'items' => ['RFID-USB-01' => 3],
                'created_at' => $today->copy()->subDays(3)->addHours(12),
                'updated_at' => $this->todayAt($anchor, 95),
                'defer_cancellation' => true,
            ],
            [
                'notes' => 'DEMO OF-1005 · Расходники для региональной сети',
                'user' => 'sever_torg',
                'warehouse' => 'SPB-01',
                'status' => OrderStatus::Completed,
                'items' => ['PRN-XP-365B' => 2, 'LBL-58X40-700' => 15],
                'created_at' => $this->todayAt($anchor, 10),
                'updated_at' => $this->todayAt($anchor, 20),
            ],
            [
                'notes' => 'DEMO OF-1006 · Комплект маркировки для магазина',
                'user' => 'alexey',
                'warehouse' => 'MSK-01',
                'status' => OrderStatus::Completed,
                'items' => ['SCN-ZEB-2278' => 1, 'PRN-XP-365B' => 1, 'LBL-58X40-700' => 20],
                'created_at' => $this->todayAt($anchor, 25),
                'updated_at' => $this->todayAt($anchor, 35),
            ],
            [
                'notes' => 'DEMO OF-1007 · RFID-учёт для складской зоны',
                'user' => 'maria',
                'warehouse' => 'MSK-01',
                'status' => OrderStatus::Completed,
                'items' => ['RFID-USB-01' => 6],
                'created_at' => $this->todayAt($anchor, 40),
                'updated_at' => $this->todayAt($anchor, 50),
            ],
            [
                'notes' => 'DEMO OF-1008 · Модернизация кассового места',
                'user' => 'sever_torg',
                'warehouse' => 'MSK-01',
                'status' => OrderStatus::Processing,
                'items' => ['POS-ATOL-S10' => 1, 'DRW-ATOL-410' => 2],
                'created_at' => $this->todayAt($anchor, 55),
                'updated_at' => $this->todayAt($anchor, 60),
            ],
            [
                'notes' => 'DEMO OF-1009 · Дооснащение магазина у дома',
                'user' => 'maria',
                'warehouse' => 'SPB-01',
                'status' => OrderStatus::Processing,
                'items' => ['DRW-ATOL-410' => 4, 'LBL-58X40-700' => 6],
                'created_at' => $this->todayAt($anchor, 65),
                'updated_at' => $this->todayAt($anchor, 70),
            ],
            [
                'notes' => 'DEMO OF-1010 · Сканеры и этикетки для открытия',
                'user' => 'alexey',
                'warehouse' => 'MSK-01',
                'status' => OrderStatus::Pending,
                'items' => ['SCN-ZEB-2278' => 2, 'LBL-58X40-700' => 10],
                'created_at' => $this->todayAt($anchor, 75),
                'updated_at' => $this->todayAt($anchor, 75),
            ],
            [
                'notes' => 'DEMO OF-1011 · Весы для торгового зала',
                'user' => 'sever_torg',
                'warehouse' => 'SPB-01',
                'status' => OrderStatus::Pending,
                'items' => ['SCL-MER-326' => 3],
                'created_at' => $this->todayAt($anchor, 80),
                'updated_at' => $this->todayAt($anchor, 80),
            ],
            [
                'notes' => 'DEMO OF-1012 · POS-терминалы для новой точки',
                'user' => 'maria',
                'warehouse' => 'MSK-01',
                'status' => OrderStatus::Pending,
                'items' => ['POS-ATOL-S10' => 3],
                'created_at' => $this->todayAt($anchor, 85),
                'updated_at' => $this->todayAt($anchor, 85),
            ],
        ];
    }

    private function todayAt(Carbon $anchor, int $percentage): Carbon
    {
        $dayStart = $anchor->copy()->startOfDay();
        $elapsedSeconds = max(0, $anchor->getTimestamp() - $dayStart->getTimestamp());

        return $dayStart->addSeconds((int) floor($elapsedSeconds * $percentage / 100));
    }
}
