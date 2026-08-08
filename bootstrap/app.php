<?php

use App\Exceptions\BusinessException;
use App\Http\Middleware\EnsurePlatformPermission;
use App\Http\Middleware\EnsurePlatformToken;
use App\Http\Middleware\EnsureTenantPermission;
use App\Http\Middleware\EnsureTenantToken;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\ResolveTenantContext;
use App\Http\Middleware\LocaleTimezoneMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', [
            RequestIdMiddleware::class,
            LocaleTimezoneMiddleware::class,
            IdempotencyMiddleware::class,
        ]);

        $middleware->alias([
            'request.id' => RequestIdMiddleware::class,
            'locale.timezone' => LocaleTimezoneMiddleware::class,
            'idempotency' => IdempotencyMiddleware::class,
            'platform.token' => EnsurePlatformToken::class,
            'platform.permission' => EnsurePlatformPermission::class,
            'tenant.context' => ResolveTenantContext::class,
            'tenant.token' => EnsureTenantToken::class,
            'tenant.permission' => EnsureTenantPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiRequest = static fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (ValidationException $exception, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return ApiResponse::validationError($exception->errors());
        });

        $exceptions->render(function (BusinessException $exception, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return ApiResponse::businessError(
                $exception->getMessage(),
                $exception->errorCode(),
                $exception->statusCode(),
                $exception->details()
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return ApiResponse::businessError('Unauthenticated.', 'AUTHENTICATION_REQUIRED', Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return ApiResponse::businessError('Forbidden.', 'FORBIDDEN', Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return ApiResponse::businessError('Resource not found.', 'NOT_FOUND', Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();
                $message = $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : Response::$statusTexts[$statusCode] ?? 'HTTP error.';

                return ApiResponse::businessError($message, 'HTTP_ERROR', $statusCode);
            }

            report($exception);

            return ApiResponse::businessError('Server error.', 'SERVER_ERROR', Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();