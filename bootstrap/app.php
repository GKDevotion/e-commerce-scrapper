<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        // Enable Sanctum stateful API (cookie-based auth for first-party SPA/mobile)
        $middleware->statefulApi();

        // Exempt webhook endpoints from CSRF (they're verified via signature instead)
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        // Rate limiting
        $middleware->throttleApi('60,1');

        // Aliases
        $middleware->alias([
            'admin'  => \App\Http\Middleware\AdminMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom 404 page
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not Found'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        // Custom 403 page
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            return response()->view('errors.403', [], 403);
        });

        // Custom 500 page
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (app()->environment('production') && !$request->expectsJson()) {
                return response()->view('errors.500', [], 500);
            }
        });
    })->create();
