<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignOperationId
{
    public const CONTEXT_KEY = 'operation_id';

    public const HEADER = 'X-Operation-ID';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incomingId = $request->header(self::HEADER);
        $operationId = is_string($incomingId) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $incomingId) === 1
            ? $incomingId
            : (string) Str::uuid();

        Context::add(self::CONTEXT_KEY, $operationId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $operationId);

        return $response;
    }
}
