<?php

use GuzzleHttp\Exception\ServerException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\Admin::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Exception $exception, Request $request) {
            if ($request->expectsJson()) {
                return error_response('internal server error', [
                    'if you\'re seeing this, FIRE the dev (-_-)',
                    $request->header('statusCode')
                ], 500);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is("api/*")) {
                return error_response($e->getMessage(), [], $e->getCode());
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is("api/*")) {
                return error_response($e->getMessage(), [], 403);
            }
        });
    })->create();
