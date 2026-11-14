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
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->hasCookie('majadigi_session')) {
                return '/?timeout=1';
            }
            return '/';
        });

        $middleware->web(append: [
            \App\Http\Middleware\CheckSessionTimeout::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return null;
            }

            \Illuminate\Support\Facades\Log::error('Exception intercepted: ' . $e->getMessage(), [
                'file' => $e->getFile() . ':' . $e->getLine(),
                'url'  => $request->fullUrl(),
            ]);

            if ($request->expectsJson() || $request->header('X-Inertia')) {
                $msg = config('app.debug') 
                    ? ($e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')') 
                    : 'Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.';
                if ($request->header('X-Inertia')) {
                    return back()->with('error', $msg);
                }
                return response()->json([
                    'message' => $msg
                ], 500);
            }

            return null;
        });
    })->create();
