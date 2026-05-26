<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        return $next($request);
    }
}
