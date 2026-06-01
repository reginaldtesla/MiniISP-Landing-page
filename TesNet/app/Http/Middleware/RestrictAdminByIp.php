<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAdminByIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('tesnet.admin_allowed_ips', []);

        if ($allowed === []) {
            return $next($request);
        }

        $clientIp = $request->ip();

        foreach ($allowed as $rule) {
            if ($this->ipMatches($clientIp, $rule)) {
                return $next($request);
            }
        }

        abort(403, 'Admin access is restricted to trusted networks.');
    }

    protected function ipMatches(string $ip, string $rule): bool
    {
        if ($rule === $ip) {
            return true;
        }

        if (! str_contains($rule, '/')) {
            return false;
        }

        [$subnet, $bits] = explode('/', $rule, 2);
        $bits = (int) $bits;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
            || filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
