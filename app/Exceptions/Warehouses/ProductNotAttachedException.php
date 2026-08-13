<?php

namespace App\Exceptions\Warehouses;

use App\Exceptions\AppException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Товар ID {$productId} не привязан к данному складу
 */
class ProductNotAttachedException extends AppException
{
    public string $errorCode = 'PRODUCT_NOT_ATTACHED_TO_WAREHOUSE';

    public int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function __construct(
        public readonly int $productId,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            "Товар ID {$productId} не привязан к данному складу.",
            $code,
            $previous,
        );
    }
}
