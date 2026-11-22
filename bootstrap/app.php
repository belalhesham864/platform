<?php

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'user.type' => \App\Http\Middleware\EnsureUserType::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::apiResponse(
                    data: null,
                    message: __('api.validation_failed'),
                    statusCode: 422,
                    errors: $exception->errors(),
                    status: false
                );
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::apiResponse(
                    data: null,
                    message: __('api.unauthenticated'),
                    statusCode: 401,
                    errors: null,
                    status: false
                );
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = $exception->getStatusCode();
                $message = match ($status) {
                    403 => __('api.unauthorized'),
                    404 => __('api.not_found'),
                    429 => __('api.too_many_requests'),
                    default => __('api.server_error'),
                };

                return ApiResponse::apiResponse(
                    data: null,
                    message: $message,
                    statusCode: $status,
                    errors: null,
                    status: false
                );
            }
        });
    })->create();
