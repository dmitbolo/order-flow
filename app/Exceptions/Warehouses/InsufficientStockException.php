<?php

namespace App\Exceptions\Warehouses;

use App\Exceptions\AppException;
use Symfony\Component\HttpFoundation\Response;

class InsufficientStockException extends AppException
{
    public string $errorCode = 'INSUFFICIENT_STOCK';

    public int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * @param  int  $productId  Product identifier.
     * @param  int  $requestedQuantity  Requested product quantity.
     * @param  int  $availableQuantity  Available product quantity.
     */
    public function __construct(
        public readonly int $productId,
        public readonly int $requestedQuantity,
        public readonly int $availableQuantity,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            "Недостаточно товара (ID: {$productId}) на складе. Требуется: {$requestedQuantity}, доступно: {$availableQuantity}.",
            $code,
            $previous,
        );
    }
}
