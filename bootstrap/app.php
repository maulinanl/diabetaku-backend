<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidSignatureException $exception) {
            return response()->view('auth.email-verified', [
                'status' => 'error',
                'title' => 'Link Verifikasi Kedaluwarsa',
                'message' => 'Link verifikasi email sudah tidak valid atau sudah melewati batas waktu penggunaan.',
                'instruction' => 'Silakan buka kembali aplikasi DiabetAku, lalu minta link verifikasi email yang baru.',
                'badgeText' => 'Link tidak valid',
                'appUrl' => config('app.mobile_deeplink', env('APP_DEEPLINK_URL', 'diabetaku://login')),
                'showOpenAppButton' => true,
            ], 403);
        });
    })->create();
