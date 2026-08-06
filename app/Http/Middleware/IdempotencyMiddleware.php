<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Placeholder for transaction-safe replay protection on write endpoints.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = trim((string) $request->headers->get('Idempotency-Key'));

        if ($idempotencyKey !== '') {
            $request->attributes->set('idempotency_key', $idempotencyKey);
        }

        return $next($request);
    }
}
