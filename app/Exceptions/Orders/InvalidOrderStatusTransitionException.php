<?php

namespace App\Exceptions\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\AppException;
use Symfony\Component\HttpFoundation\Response;

class InvalidOrderStatusTransitionException extends AppException
{
    public string $errorCode = 'INVALID_ORDER_STATUS_TRANSITION';

    public int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function __construct(OrderStatus $from, OrderStatus $to)
    {
        parent::__construct("Нельзя изменить статус заказа с «{$from->label()}» на «{$to->label()}».");
    }
}
