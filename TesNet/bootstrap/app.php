<?php

use App\Http\Middleware\AdminIdleTimeout;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureStudent;
use App\Http\Middleware\EnsureSinglePortalSession;
use App\Http\Middleware\RestrictAdminByIp;
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
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'student' => EnsureStudent::class,
            'admin.ip' => RestrictAdminByIp::class,
            'admin.idle' => AdminIdleTimeout::class,
            'portal.single_session' => EnsureSinglePortalSession::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin', 'admin/*')) {
                return route('admin.login');
            }

            return route('portal.login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->routeIs('portal.login', 'portal.register')) {
                return $request->user()?->isAdmin()
                    ? route('admin.dashboard')
                    : route('portal.dashboard');
            }

            return $request->user()?->isAdmin()
                ? route('admin.dashboard')
                : route('portal.dashboard');
        });

        $middleware->validateCsrfTokens(except: [
            'portal/payments/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
