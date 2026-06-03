<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSinglePortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        $sessionVersion = $request->session()->get('portal_session_version');
        $currentVersion = (int) ($user->portal_session_version ?? 0);

        if ($sessionVersion === null || (int) $sessionVersion !== $currentVersion) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login')
                ->withErrors([
                    'phone_number' => 'This account was signed in on another device. Log in again to use TesNet here.',
                ]);
        }

        return $next($request);
    }
}
