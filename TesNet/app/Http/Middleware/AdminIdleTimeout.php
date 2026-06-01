<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminIdleTimeout
{
    public const SESSION_KEY = 'admin_last_activity';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            return $next($request);
        }

        $minutes = max(1, (int) config('tesnet.admin_idle_logout_minutes', 5));
        $timeoutSeconds = $minutes * 60;
        $lastActivity = $request->session()->get(self::SESSION_KEY);

        if (is_int($lastActivity) && (now()->timestamp - $lastActivity) > $timeoutSeconds) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'phone_number' => 'Your admin session expired after '.$minutes.' minutes of inactivity. Please sign in again.',
                ]);
        }

        $request->session()->put(self::SESSION_KEY, now()->timestamp);

        return $next($request);
    }
}
