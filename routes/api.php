<?php
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\WarehouseProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Защищенные API маршруты (требуют Bearer Token)
    Route::middleware('')->group(function () {
        // Получение товаров конкретного склада с ценами
        Route::get('/warehouses/{warehouse}', [WarehouseController::class, 'show']);
        Route::get('/warehouses/{warehouse}/products', [WarehouseProductController::class, 'index']);

        // Создание и просмотр заказов
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
    });
});
