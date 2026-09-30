<?php

use App\Exceptions\AppException;
use App\Support\EnvironmentValidator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Broadcast channel auth uses API tokens (not web sessions).
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Localization for every API request: Accept-Language -> user.locale -> ar
        $middleware->appendToGroup('api', \App\Http\Middleware\SetLocale::class);

        // Must run before auth: the framework sorts `auth` before the `api`
        // group, so a guest 401/403 would otherwise render before locale is set.
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\SetLocale::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // AppException: { code(message key), params, httpStatus } -> translated JSON
        $exceptions->render(function (AppException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => __($e->messageKey, $e->params),
                'code' => $e->errorCode,
            ], $e->httpStatus);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => __('messages.validation_failed'),
                'code' => 'VALIDATION_FAILED',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => __('messages.unauthenticated'),
                'code' => 'UNAUTHENTICATED',
            ], 401);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            return response()->json([
                'success' => false,
                'message' => __($status === 404 ? 'messages.not_found' : 'messages.http_error'),
                'code' => $status === 404 ? 'NOT_FOUND' : 'HTTP_ERROR',
            ], $status);
        });
    })
    ->create();
