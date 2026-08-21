<?php

namespace App\Http\Resources\Api\V1;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockMovement */
class StockMovementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse' => [
                'id' => $this->warehouse_id,
                'name' => $this->whenLoaded('warehouse', fn () => $this->warehouse->name),
            ],
            'product' => [
                'id' => $this->product_id,
                'name' => $this->whenLoaded('product', fn () => $this->product->name),
                'sku' => $this->whenLoaded('product', fn () => $this->product->sku),
            ],
            'order_id' => $this->order_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'quantity_change' => $this->quantity_change,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
