<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int|null $order_id
 * @property int|null $actor_id
 * @property StockMovementType $type
 * @property int $quantity_change
 * @property int $quantity_before
 * @property int $quantity_after
 * @property string|null $comment
 * @property Carbon $created_at
 * @property-read Warehouse $warehouse
 * @property-read Product $product
 * @property-read Order|null $order
 * @property-read User|null $actor
 */
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'order_id',
        'actor_id',
        'type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'comment',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'quantity_change' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
