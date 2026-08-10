<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WarehouseProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,

            'price' => $this->whenPivotLoaded(WarehouseProduct::class, fn () => $this->pivot->price),
            'stock_quantity' => $this->whenPivotLoaded(WarehouseProduct::class, fn () => $this->pivot->stock_quantity),
        ];
    }
}
