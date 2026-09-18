<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/plain');
            echo "ORIGINAL EXCEPTION BEFORE RENDER CRASH:\n\n";
            echo $e->getMessage() . "\n\n";
            echo $e->getFile() . ":" . $e->getLine() . "\n\n";
            echo $e->getTraceAsString();
            die();
        });
        
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );
    })->create();
