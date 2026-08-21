<?php

namespace App\Models;

use Database\Factories\WarehouseProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int $price
 * @property int $stock_quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\WarehouseProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct whereStockQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseProduct whereWarehouseId($value)
 *
 * @mixin \Eloquent
 */
class WarehouseProduct extends Pivot
{
    /** @use HasFactory<WarehouseProductFactory> */
    use HasFactory;

    protected $table = 'warehouse_product';

    public $incrementing = true;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
        'stock_quantity' => 'integer',
    ];
}
