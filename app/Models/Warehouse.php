<?php

namespace App\Models;

use App\Exceptions\Warehouses\InsufficientStockException;
use App\Exceptions\Warehouses\ProductNotAttachedException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \App\Models\WarehouseProduct|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\WarehouseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'warehouse_product')
            ->using(WarehouseProduct::class)
            ->withPivot('price', 'stock_quantity')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @param Collection<OrderItem> $items
     * @return void
     */
    public function incrementProductStocks(Collection $items): void
    {
        $this->updateProductStocks($items, '+');
    }

    /**
     * @param array<int, int> $quantities [product_id => quantity]
     * @return array<int, float> [product_id => price]
     * @throws InsufficientStockException|ProductNotAttachedException
     */
    public function decrementProductStocks(array $quantities): array
    {
        if (empty($quantities)) {
            return [];
        }

        $warehouseProducts = WarehouseProduct::where('warehouse_id', $this->id)
            ->whereIn('product_id', array_keys($quantities))
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        $prices = [];
        $itemsForUpdate = [];

        foreach ($quantities as $productId => $quantity) {
            $warehouseProduct  = $warehouseProducts->get($productId);

            if (! $warehouseProduct ) {
                throw new ProductNotAttachedException($productId);
            }

            if ($warehouseProduct->stock_quantity < $quantity) {
                throw new InsufficientStockException(
                    $productId,
                    $quantity,
                    $warehouseProduct->stock_quantity
                );
            }

            $prices[$productId] = $warehouseProduct->price;

            $itemsForUpdate[] = (object)[
                'product_id' => $productId,
                'quantity' => $quantity
            ];
        }

        $this->updateProductStocks(collect($itemsForUpdate), '-');

        return $prices;
    }

    /**
     * Execute a single optimized bulk UPDATE query instead of N individual queries.
     *
     * @param Collection $items
     * @param string $operator
     * @return void
     */
    private function updateProductStocks(Collection $items, string $operator): void
    {

        if ($items->isEmpty()) {
            return;
        }

        $cases = [];
        $bindings = [];

        foreach ($items as $item) {
            $cases[] = "WHEN product_id = ? THEN stock_quantity {$operator} ?";
            $bindings[] = $item->product_id;
            $bindings[] = $item->quantity;
        }

        $rawCases = implode(' ', $cases);

        $bindings[] = $this->id;

        $productIds = $items->pluck('product_id')->toArray();
        $whereInPlaceholders = implode(',', array_fill(0, count($productIds), '?'));

        foreach ($productIds as $id) {
            $bindings[] = $id;
        }

        DB::statement("
            UPDATE warehouse_product
            SET stock_quantity = CASE {$rawCases} ELSE stock_quantity END
            WHERE warehouse_id = ? AND product_id IN ({$whereInPlaceholders})
        ", $bindings);
    }
}
