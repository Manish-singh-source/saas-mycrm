<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'platform.token' => App\Http\Middleware\EnsurePlatformToken::class,
            'tenant.context' => App\Http\Middleware\ResolveTenantContext::class,
            'tenant.token' => App\Http\Middleware\EnsureTenantToken::class,
            'tenant.permission' => App\Http\Middleware\EnsureTenantPermission::class,
            'abilities' => Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
        $middleware->append(App\Http\Middleware\LogApiRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, \Illuminate\Http\Request $request) {
            if (! $request->is('api/auth/v1/tenants/*', 'api/platform/v1/tenants', 'api/platform/v1/tenants/*')) return null;
            if ($exception instanceof \Illuminate\Http\Exceptions\HttpResponseException) return $exception->getResponse();
            if ($exception instanceof \Illuminate\Validation\ValidationException) return \App\Support\ApiResponse::validation($exception->errors());
            $status = match (true) {
                $exception instanceof \Illuminate\Auth\AuthenticationException => 401,
                $exception instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };
            $message = match ($status) {
                401 => 'Unauthenticated.', 403 => 'You do not have permission to perform this action.',
                404 => 'Resource not found.', 405 => 'Method not allowed.', 429 => 'Too many requests.',
                default => 'Something went wrong. Please try again later.',
            };
            $response = \App\Support\ApiResponse::error($message, $status);
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) $response->headers->add($exception->getHeaders());
            return $response;
        });
    })->create();





