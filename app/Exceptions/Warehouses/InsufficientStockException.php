<?php

namespace App\Exceptions\Warehouses;

use App\Exceptions\AppException;
use Symfony\Component\HttpFoundation\Response;

class InsufficientStockException extends AppException
{
    public string $errorCode = 'INSUFFICIENT_STOCK';

    public int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * @param  int  $productId  Идентификатор товара.
     * @param  int  $requestedQuantity  Сколько единиц товара было запрошено.
     * @param  int  $availableQuantity  Сколько единиц фактически доступно на складе.
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
