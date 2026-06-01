<?php

namespace App\Services;

use App\Models\RadAcct;
use App\Support\SessionDisconnectResult;
use Illuminate\Support\Facades\Log;

class SessionDisconnectService
{
    public function __construct(
        protected MikrotikApiService $mikrotik,
    ) {}

    public function forceDisconnect(RadAcct $session): SessionDisconnectResult
    {
        $username = $session->username;
        $mac = $session->callingstationid ?: null;
        $ip = $session->framedipaddress ?: null;
        $routerAttempted = $this->mikrotik->isEnabled() && ($mac || $ip);
        $routerKicked = false;

        if ($routerAttempted) {
            try {
                $routerKicked = $this->mikrotik->disconnectHotspotSession($username, $mac, $ip);
            } catch (\Throwable $exception) {
                Log::error('MikroTik disconnect failed', [
                    'username' => $username,
                    'error' => $exception->getMessage(),
                ]);
            }
        } elseif ($this->mikrotik->isEnabled()) {
            Log::warning('Session disconnect missing MAC/IP; skipping MikroTik kick', [
                'username' => $username,
                'radacctid' => $session->radacctid,
            ]);
        }

        $accountingClosed = false;

        if (! $routerAttempted || $routerKicked) {
            if ($session->acctstoptime === null) {
                $session->update([
                    'acctstoptime' => now(),
                    'acctterminatecause' => 'Admin-Reset',
                ]);
            }

            $accountingClosed = true;
        }

        return new SessionDisconnectResult($accountingClosed, $routerKicked, $routerAttempted);
    }
}
