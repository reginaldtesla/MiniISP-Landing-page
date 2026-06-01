<?php

namespace App\Observers;

use App\Models\User;
use App\Services\RadiusSyncService;

class UserObserver
{
    public function __construct(
        protected RadiusSyncService $radius,
    ) {}

    public function created(User $user): void
    {
        $plain = $user->getPlainPasswordForRadius();

        if ($plain && $user->phone_number) {
            $this->radius->syncUser($user, $plain);
        }
    }

    public function updated(User $user): void
    {
        if (! $user->phone_number) {
            return;
        }

        if ($user->wasChanged('password')) {
            $plain = $user->getPlainPasswordForRadius();

            if ($plain) {
                $this->radius->updatePassword($user, $plain);
            }
        }

        if ($user->wasChanged('device_limit')) {
            $this->radius->updateDeviceLimit($user);
        }

        if ($user->wasChanged('is_suspended')) {
            $this->radius->updateSuspension($user);
        }
    }

    public function deleted(User $user): void
    {
        $this->radius->removeUser($user);
    }
}
