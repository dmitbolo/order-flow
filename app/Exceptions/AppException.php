<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

abstract class AppException extends Exception
{
    public protected(set) string $errorCode = 'INTERNAL_ERROR';
    public protected(set) int $statusCode = Response::HTTP_BAD_REQUEST;
    public string $errorMessage {
        get {
            if (!empty($this->message)) {
                return $this->message;
            }

            return $this->errorCode
                    |> strtolower(...)
                    |> ucfirst(...)
                    |> (fn($x) => str_replace('_', ' ', $x));
        }
    }

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

