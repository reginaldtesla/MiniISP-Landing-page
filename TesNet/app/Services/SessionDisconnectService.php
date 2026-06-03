<?php

namespace App\Services;

use App\Models\RadAcct;
use App\Models\User;
use App\Support\PackageUsage;
use App\Support\PhoneNumber;
use App\Support\SessionDisconnectResult;
use Illuminate\Support\Facades\Log;

class SessionDisconnectService
{
    public function __construct(
        protected MikrotikApiService $mikrotik,
    ) {}

    public function forceDisconnect(RadAcct $session): SessionDisconnectResult
    {
        $this->recordUsageBeforeClose($session);

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

    protected function recordUsageBeforeClose(RadAcct $session): void
    {
        $phone = PhoneNumber::normalize($session->username);

        if ($phone === '') {
            return;
        }

        $user = User::query()->where('phone_number', $phone)->first();

        if (! $user) {
            return;
        }

        try {
            PackageUsage::syncConsumptionForUser($user);
        } catch (\Throwable $exception) {
            Log::warning('Could not record usage before session close', [
                'username' => $session->username,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
