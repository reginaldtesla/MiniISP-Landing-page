<?php

namespace App\Services;

use App\Models\RadAcct;
use Illuminate\Support\Facades\Log;

class SessionDisconnectService
{
    public function __construct(
        protected MikrotikApiService $mikrotik,
    ) {}

    public function forceDisconnect(RadAcct $session): bool
    {
        $username = $session->username;
        $routerOk = false;

        if ($this->mikrotik->isEnabled()) {
            try {
                $routerOk = $this->mikrotik->disconnectHotspotUser($username);
            } catch (\Throwable $exception) {
                Log::error('MikroTik disconnect failed', [
                    'username' => $username,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($session->acctstoptime === null) {
            $session->update([
                'acctstoptime' => now(),
                'acctterminatecause' => 'Admin-Reset',
            ]);
        }

        return $routerOk || $session->acctstoptime !== null;
    }
}
