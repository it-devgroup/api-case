<?php

use App\Http\Middleware\EnsureTokenOwnerIsAdmin;
use App\Http\Middleware\EnsureTokenOwnerIsUser;
use App\Support\JsonApi;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: [
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/admin.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.admin' => EnsureTokenOwnerIsAdmin::class,
            'auth.user' => EnsureTokenOwnerIsUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Order matters: these are tried in registration order, most
        // specific first, falling through to the generic HTTP-exception
        // and catch-all handlers below.

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $errors = [];

            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $errors[] = JsonApi::errorObject(
                        '422',
                        'Unprocessable Entity',
                        $message,
                        "/data/attributes/{$field}",
                    );
                }
            }

            return JsonApi::errors($errors, 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return JsonApi::error('401', 'Unauthorized', 'Authentication required.');
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            return JsonApi::error(
                (string) $status,
                SymfonyResponse::$statusTexts[$status] ?? 'Error',
                $e->getMessage() ?: null,
            );
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') || app()->hasDebugModeEnabled()) {
                return null;
            }

            return JsonApi::error('500', 'Server Error', 'An unexpected error occurred.');
        });
    })->create();
