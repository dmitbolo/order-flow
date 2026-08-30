<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Регистрирует все кастомные обработчики для API.
     */
    public static function configure(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        self::registerNotFoundHandler($exceptions);
        self::registerValidationHandler($exceptions);
        self::registerAuthHandlers($exceptions);
        self::registerAppExceptionHandler($exceptions);
        self::registerFallbackHandler($exceptions);
    }

    private static function registerNotFoundHandler(Exceptions $exceptions): void
    {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e->getPrevious() instanceof ModelNotFoundException) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Запрашиваемая запись не найдена.',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status' => 'error',
                'error_code' => 'ROUTE_NOT_FOUND',
                'message' => 'Маршрут не найден.',
            ], Response::HTTP_NOT_FOUND);
        });
    }

    private static function registerValidationHandler(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'error_code' => 'VALIDATION_ERROR',
                'message' => 'Переданные данные не прошли валидацию.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });
    }

    private static function registerAuthHandlers(Exceptions $exceptions): void
    {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'error_code' => 'UNAUTHENTICATED',
                'message' => 'Необходима авторизация.',
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'error_code' => 'ACCESS_DENIED',
                'message' => 'Доступ запрещен.',
            ], Response::HTTP_FORBIDDEN);
        });
    }

    private static function registerFallbackHandler(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (
                ! $request->is('api/*')
                || config('app.debug')
                || $e instanceof HttpExceptionInterface
            ) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'error_code' => 'INTERNAL_SERVER_ERROR',
                'message' => 'На сервере произошла ошибка.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }

    private static function registerAppExceptionHandler(Exceptions $exceptions): void
    {
        $exceptions->render(function (AppException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'error_code' => $e->errorCode,
                'message' => $e->errorMessage,
            ], $e->statusCode);
        });
    }
}
