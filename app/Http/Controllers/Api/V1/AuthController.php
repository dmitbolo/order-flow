<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $token = $action->execute(
            email: $request->validated('email'),
            password: $request->validated('password')
        );

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], Response::HTTP_OK);
    }

    /**
     * Получить информацию о текущем авторизованном пользователе.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Выход из системы (отзыв текущего токена).
     */
    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->execute($request->user());

        return response()->json([
            'message' => 'Успешный выход из системы',
        ], Response::HTTP_OK);
    }
}
