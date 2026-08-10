<?php

namespace App\Models;

use App\DTO\CreateOrderData;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $warehouse_id
 * @property string $status
 * @property int $total_amount
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Database\Factories\OrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereWarehouseId($value)
 * @mixin \Eloquent
 */
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'status' => OrderStatus::class,
    ];

    public string $statusLabel {
        get => $this->status->label();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function createFromData(User $user, Warehouse $warehouse, CreateOrderData $data, array $prices): self
    {
        $totalAmount = 0;
        $itemsToCreate = [];

        foreach ($data->items as $itemData) {
            $price = $prices[$itemData->productId];

            $totalAmount += $price * $itemData->quantity;

            $itemsToCreate[] = [
                'product_id' => $itemData->productId,
                'quantity'   => $itemData->quantity,
                'price'      => $price,
            ];
        }

        /** @var Order $order */
        $order = self::create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'pending',
            'total_amount' => $totalAmount,
            'notes' => $data->notes,
        ]);

        $order->items()->createMany($itemsToCreate);

        return $order;
    }
}
