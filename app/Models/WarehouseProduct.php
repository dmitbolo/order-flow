<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class WarehouseProduct extends Pivot
{
    /** @use HasFactory<\Database\Factories\WarehouseProductFactory> */
    use HasFactory;

    // Указываем имя таблицы явно, так как это Pivot
    protected $table = 'warehouse_product';

    public $incrementing = true;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'price',
        'stock_quantity',
    ];

    protected $casts = [
        'price' => 'integer',
        'stock_quantity' => 'integer',
    ];
}
