<?php

use App\Http\Middleware\EnsureAuthenticatedUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum may authenticate first-party AJAX calls with the web session.
        $middleware->statefulApi();

        // A readable alias keeps route declarations concise.
        $middleware->alias([
            'user.auth' => EnsureAuthenticatedUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API consumers always receive JSON, even if they omit the Accept header.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        // Do not expose model names or database details in a public 404 response.
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (
                $request->is('api/users/*')
                && $exception->getPrevious() instanceof ModelNotFoundException
            ) {
                return response()->json([
                    'message' => 'Користувача не знайдено.',
                ], 404);
            }
        });
    })->create();
