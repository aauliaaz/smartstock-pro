<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Models\ErrorLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\Throwable $e) {
            $error = ErrorLog::create([
                'severity' => 'ERROR',
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'payload' => request()->all(),
                'user_id' => Auth::id()
            ]);

            // Notify Admin
            $admin = User::whereHas('role', function($q) { $q->where('slug', 'admin'); })->first();
            if ($admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'System Exception: ' . Str::limit($e->getMessage(), 50),
                    'message' => "Terjadi error pada sistem. ID Log: {$error->id}. Silakan cek Error Log Dashboard.",
                    'type' => 'CRITICAL'
                ]);
            }
        });
    })->create();
