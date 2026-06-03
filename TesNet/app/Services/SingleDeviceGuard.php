<?php

namespace App\Services;

use App\Models\RadAcct;
use App\Models\User;
use App\Support\PackageUsage;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Enforce one student account → one portal session → one hotspot device at a time.
 */
class SingleDeviceGuard
{
    public function __construct(
        protected MikrotikApiService $mikrotik,
        protected SessionDisconnectService $disconnect,
    ) {}

    public function studentDeviceLimit(): int
    {
        return max(1, (int) config('tesnet.student_device_limit', 1));
    }

    public function bindPortalSession(User $user): int
    {
        $user->increment('portal_session_version');

        return (int) $user->fresh()->portal_session_version;
    }

    public function disconnectOtherHotspotSessions(User $user): void
    {
        $usernames = $this->hotspotUsernamesFor($user);

        if ($usernames === []) {
            return;
        }

        try {
            RadAcct::query()
                ->active()
                ->whereIn('username', $usernames)
                ->orderByDesc('acctstarttime')
                ->get()
                ->each(fn (RadAcct $session) => $this->disconnect->forceDisconnect($session));
        } catch (Throwable $exception) {
            Log::warning('Could not disconnect RADIUS sessions for single-device policy', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if (! $this->mikrotik->isEnabled()) {
            return;
        }

        try {
            foreach ($usernames as $username) {
                $this->mikrotik->disconnectHotspotUser($username);
            }
        } catch (Throwable $exception) {
            Log::warning('Could not disconnect MikroTik sessions for single-device policy', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function hotspotUsernamesFor(User $user): array
    {
        $usernames = [];

        if ($user->phone_number) {
            $usernames = PackageUsage::usernameVariantsFor($user->phone_number);
        }

        $purchase = PackageUsage::activePurchaseForDisplay($user);

        if ($purchase?->mikrotik_username) {
            $usernames[] = $purchase->mikrotik_username;
        }

        return array_values(array_unique($usernames));
    }
}
