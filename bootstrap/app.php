<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // This handles the redirect-to-login issue for API requests
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please provide a valid token.',
                ], 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $fetchDestination = $request->header('Sec-Fetch-Dest');

            $shouldKeepNotFoundResponse = $request->is('api/*')
                || $request->expectsJson()
                || $request->ajax()
                || !$request->acceptsHtml()
                || ($fetchDestination !== null && $fetchDestination !== 'document')
                || $request->path() === '/';

            if ($shouldKeepNotFoundResponse) {
                return null;
            }

            return redirect()->route('website.home.index', ['not_found' => 1]);
        });
    })->create();
