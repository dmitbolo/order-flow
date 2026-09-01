<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/login',
        summary: 'Issue an API token',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Token issued', content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
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
    #[OA\Get(
        path: '/me',
        summary: 'Get the current user',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Current user', content: new OA\JsonContent(required: ['data'], properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')], type: 'object')),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
        ],
    )]
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Выход из системы (отзыв текущего токена).
     */
    #[OA\Post(
        path: '/logout',
        summary: 'Revoke the current API token',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Token revoked', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(ref: '#/components/responses/UnauthorizedError', response: 401),
        ],
    )]
    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->execute($request->user());

        return response()->json([
            'message' => 'Успешный выход из системы',
        ], Response::HTTP_OK);
    }
}
