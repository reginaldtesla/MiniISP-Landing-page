<?php

namespace App\Services;

use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\User;

class RadiusSyncService
{
    public function syncUser(User $user, string $plainPassword): void
    {
        $username = $user->phone_number;

        if (! $username) {
            return;
        }

        $this->upsertCheck($username, 'Cleartext-Password', ':=', $plainPassword);
        $this->upsertCheck($username, 'Simultaneous-Use', ':=', (string) $user->device_limit);
        $this->syncSuspension($user);
    }

    public function updatePassword(User $user, string $plainPassword): void
    {
        $this->upsertCheck($user->phone_number, 'Cleartext-Password', ':=', $plainPassword);
    }

    public function updateDeviceLimit(User $user): void
    {
        $this->upsertCheck($user->phone_number, 'Simultaneous-Use', ':=', (string) $user->device_limit);
    }

    public function updateSuspension(User $user): void
    {
        $this->syncSuspension($user);
    }

    public function applyPackageLimits(User $user, ?int $speedMbps, ?int $dataLimitMb): void
    {
        if ($dataLimitMb) {
            $this->applyDataLimitBytes($user, $speedMbps, $dataLimitMb * 1048576);
        } else {
            $this->applyDataLimitBytes($user, $speedMbps, 0);
        }
    }

    public function applyDataLimitBytes(User $user, ?int $speedMbps, int $limitBytes): void
    {
        $username = $user->phone_number;

        RadReply::query()->where('username', $username)->whereIn('attribute', [
            'Mikrotik-Rate-Limit',
            'Mikrotik-Total-Limit',
        ])->delete();

        if ($speedMbps) {
            $this->upsertReply($username, 'Mikrotik-Rate-Limit', ':=', $speedMbps.'M/'.$speedMbps.'M');
        }

        if ($limitBytes > 0) {
            $this->upsertReply($username, 'Mikrotik-Total-Limit', ':=', (string) $limitBytes);
        }
    }

    public function removeUser(User $user): void
    {
        $username = $user->phone_number;

        RadCheck::query()->where('username', $username)->delete();
        RadReply::query()->where('username', $username)->delete();
    }

    protected function upsertCheck(string $username, string $attribute, string $op, string $value): void
    {
        RadCheck::query()->updateOrCreate(
            ['username' => $username, 'attribute' => $attribute],
            ['op' => $op, 'value' => $value]
        );
    }

    protected function upsertReply(string $username, string $attribute, string $op, string $value): void
    {
        RadReply::query()->updateOrCreate(
            ['username' => $username, 'attribute' => $attribute],
            ['op' => $op, 'value' => $value]
        );
    }

    protected function syncSuspension(User $user): void
    {
        $username = $user->phone_number;

        if (! $username) {
            return;
        }

        if ($user->is_suspended) {
            $this->upsertCheck($username, 'Auth-Type', ':=', 'Reject');
        } else {
            RadCheck::query()
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->where('value', 'Reject')
                ->delete();
        }
    }
}
