<?php

namespace App\Exceptions\Products;

use App\Exceptions\AppException;
use Symfony\Component\HttpFoundation\Response;

class ProductUnavailableException extends AppException
{
    public string $errorCode = 'PRODUCT_UNAVAILABLE';

    public int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function __construct(public readonly int $productId)
    {
        parent::__construct("Товар ID {$productId} недоступен для заказа.");
    }
}
