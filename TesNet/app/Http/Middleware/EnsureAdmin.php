<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->isAdmin()) {
            return redirect()
                ->route('portal.dashboard')
                ->withErrors(['auth' => 'Student accounts use the portal to buy data and connect to Wi‑Fi.']);
        }

        return $next($request);
    }
}
