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
        $exceptions->render(function (\Illuminate\Database\QueryException $e, Request $request) {
            \Illuminate\Support\Facades\Log::error('Database QueryException intercepted: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
            ]);

            if ($request->expectsJson() || $request->header('X-Inertia')) {
                if ($request->header('X-Inertia')) {
                    return back()->with('error', 'Terjadi kesalahan saat memproses data. Silakan periksa kembali input Anda.');
                }
                return response()->json([
                    'message' => 'Terjadi kesalahan saat memproses data. Silakan periksa kembali input Anda.'
                ], 500);
            }

            return response()->view('errors.500', [
                'message' => 'Terjadi kesalahan saat memproses data. Silakan periksa kembali input Anda.'
            ], 500);
        });

        $exceptions->render(function (\PDOException $e, Request $request) {
            \Illuminate\Support\Facades\Log::error('Database PDOException intercepted: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
            ]);

            if ($request->expectsJson() || $request->header('X-Inertia')) {
                if ($request->header('X-Inertia')) {
                    return back()->with('error', 'Terjadi kesalahan saat memproses data. Silakan periksa kembali input Anda.');
                }
                return response()->json([
                    'message' => 'Terjadi kesalahan saat memproses data. Silakan periksa kembali input Anda.'
                ], 500);
            }

            return response()->view('errors.500', [
                'message' => 'Terjadi kesalahan saat memproses data. Silakan periksa kembali input Anda.'
            ], 500);
        });
    })->create();
