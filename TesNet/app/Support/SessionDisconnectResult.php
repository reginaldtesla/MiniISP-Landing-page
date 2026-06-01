<?php

namespace App\Support;

class SessionDisconnectResult
{
    public function __construct(
        public bool $accountingClosed,
        public bool $routerKicked,
        public bool $routerAttempted,
    ) {}

    public function succeeded(): bool
    {
        return $this->accountingClosed;
    }

    public function userMessage(string $successFallback = 'Session disconnected.'): string
    {
        if (! $this->accountingClosed) {
            if ($this->routerAttempted && ! $this->routerKicked) {
                return 'Could not reach the router to disconnect this device. It may still be online — try again in a moment.';
            }

            return 'Could not disconnect that session.';
        }

        if ($this->routerAttempted && ! $this->routerKicked) {
            return 'Session updated, but the router could not be reached. The device may still be online briefly.';
        }

        return $successFallback;
    }
}
