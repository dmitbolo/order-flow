<?php

namespace App\Exceptions\Orders;

use App\Exceptions\AppException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Нельзя отменить заказ, который уже обработан или отменен
 */
class OrderCannotBeCanceledException extends AppException
{
    public string $errorCode = 'ORDER_CANNOT_BE_CANCELED';

    public int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected $message = 'Нельзя отменить заказ, который уже обработан или отменен.';
}
