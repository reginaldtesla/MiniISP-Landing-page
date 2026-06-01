<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return redirect()
                ->route('admin.dashboard')
                ->withErrors(['auth' => 'Administrator accounts use the Admin Hub only.']);
        }

        if ($user?->is_suspended) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('portal.login')
                ->withErrors(['phone_number' => 'Your account is suspended. Contact support.']);
        }

        return $next($request);
    }
}
