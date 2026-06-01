<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MikrotikApiService
{
    protected mixed $socket = null;

    public function isEnabled(): bool
    {
        return (bool) config('mikrotik.api.enabled')
            && config('mikrotik.api.password') !== '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listHotspotActive(): array
    {
        if (! $this->connect()) {
            return [];
        }

        try {
            return $this->command('/ip/hotspot/active/print');
        } finally {
            $this->disconnect();
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->isEnabled()) {
            return [
                'ok' => false,
                'message' => 'MIKROTIK_API_ENABLED=false or API password missing.',
            ];
        }

        $host = config('mikrotik.api.host');
        $port = config('mikrotik.api.port');

        if (! $this->connect()) {
            return [
                'ok' => false,
                'message' => "Could not reach MikroTik API at {$host}:{$port}. Check host, port, firewall, and API service.",
            ];
        }

        try {
            $identity = $this->command('/system/identity/print');

            if ($this->responseFailed($identity)) {
                return [
                    'ok' => false,
                    'message' => 'MikroTik login failed — check MIKROTIK_API_USER and MIKROTIK_API_PASSWORD.',
                ];
            }

            $routerName = $identity[0]['name'] ?? 'router';
            $activeSessions = $this->filterHotspotSessions('');

            return [
                'ok' => true,
                'message' => 'API login OK on "'.$routerName.'" · '.count($activeSessions).' active hotspot session(s).',
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'MikroTik API error: '.$exception->getMessage(),
            ];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $response
     */
    protected function responseFailed(array $response): bool
    {
        foreach ($response as $row) {
            if (($row['!type'] ?? null) === '!trap' || ($row['!type'] ?? null) === '!fatal') {
                return true;
            }
        }

        return false;
    }

    public function disconnectHotspotUser(string $username): bool
    {
        return $this->disconnectHotspotSession($username);
    }

    public function disconnectHotspotSession(
        string $username,
        ?string $macAddress = null,
        ?string $ipAddress = null,
    ): bool {
        if (! $this->connect()) {
            return false;
        }

        try {
            $sessions = $this->filterHotspotSessions($username, $macAddress, null);

            if ($sessions === [] && $ipAddress !== null) {
                $sessions = $this->filterHotspotSessions($username, null, $ipAddress);
            }

            if ($sessions === [] && $macAddress === null && $ipAddress === null) {
                $sessions = $this->filterHotspotSessions($username);
            }

            if ($sessions === []) {
                return false;
            }

            $removed = false;

            foreach ($sessions as $session) {
                if (isset($session['.id'])) {
                    $this->command('/ip/hotspot/active/remove', ['.id' => $session['.id']]);
                    $removed = true;
                }
            }

            return $removed;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function filterHotspotSessions(
        string $username,
        ?string $macAddress = null,
        ?string $ipAddress = null,
    ): array {
        $arguments = $username !== '' ? ['?user' => $username] : [];
        $sessions = $this->command('/ip/hotspot/active/print', $arguments);
        $targetMac = $this->normalizeMac($macAddress);
        $targetIp = $this->normalizeIp($ipAddress);

        $matches = [];

        foreach ($sessions as $session) {
            if (isset($session['!type'])) {
                continue;
            }

            if ($targetMac !== null) {
                if ($this->normalizeMac($session['mac-address'] ?? null) !== $targetMac) {
                    continue;
                }
            } elseif ($targetIp !== null) {
                if ($this->normalizeIp($session['address'] ?? null) !== $targetIp) {
                    continue;
                }
            }

            $matches[] = $session;
        }

        return $matches;
    }

    protected function normalizeMac(?string $mac): ?string
    {
        if ($mac === null || $mac === '') {
            return null;
        }

        $clean = strtoupper(preg_replace('/[^0-9A-F]/', '', $mac) ?? '');

        return strlen($clean) === 12 ? $clean : null;
    }

    protected function normalizeIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    protected function connect(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $host = config('mikrotik.api.host');
        $port = config('mikrotik.api.port');
        $ssl = config('mikrotik.api.ssl');
        $timeout = 5;

        $address = ($ssl ? 'ssl://' : '').$host;
        $this->socket = @fsockopen($address, $port, $errno, $errstr, $timeout);

        if (! $this->socket) {
            Log::warning('MikroTik API connection failed', ['error' => $errstr]);

            return false;
        }

        stream_set_timeout($this->socket, $timeout);

        if ($ssl) {
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_TLS_CLIENT);
        }

        $this->writeWord('');
        $response = $this->readResponse();

        if (! $this->isTrap($response)) {
            $this->login();

            return true;
        }

        if (($response[0]['message'] ?? '') === 'Connected') {
            $this->login();

            return true;
        }

        return false;
    }

    protected function login(): void
    {
        $this->command('/login', [
            'name' => config('mikrotik.api.user'),
            'password' => config('mikrotik.api.password'),
        ]);
    }

    protected function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<int, array<string, mixed>>
     */
    protected function command(string $command, array $arguments = []): array
    {
        $this->writeWord($command);

        foreach ($arguments as $key => $value) {
            $this->writeWord('='.$key.'='.$value);
        }

        $this->writeWord('');

        return $this->readResponse();
    }

    protected function writeWord(string $word): void
    {
        $length = strlen($word);

        if ($length < 0x80) {
            fwrite($this->socket, chr($length));
        } elseif ($length < 0x4000) {
            fwrite($this->socket, chr(($length >> 8) | 0x80));
            fwrite($this->socket, chr($length & 0xFF));
        } elseif ($length < 0x200000) {
            fwrite($this->socket, chr(($length >> 16) | 0xC0));
            fwrite($this->socket, chr(($length >> 8) & 0xFF));
            fwrite($this->socket, chr($length & 0xFF));
        } else {
            fwrite($this->socket, chr(($length >> 24) | 0xE0));
            fwrite($this->socket, chr(($length >> 16) & 0xFF));
            fwrite($this->socket, chr(($length >> 8) & 0xFF));
            fwrite($this->socket, chr($length & 0xFF));
        }

        if ($length > 0) {
            fwrite($this->socket, $word);
        }
    }

    protected function readWord(): ?string
    {
        $byte = fread($this->socket, 1);

        if ($byte === '' || $byte === false) {
            return null;
        }

        $length = ord($byte);

        if (($length & 0x80) === 0x80) {
            if (($length & 0xC0) === 0x80) {
                $length = (($length & 0x3F) << 8) + ord(fread($this->socket, 1));
            } elseif (($length & 0xE0) === 0xC0) {
                $length = (($length & 0x1F) << 16)
                    + (ord(fread($this->socket, 1)) << 8)
                    + ord(fread($this->socket, 1));
            } elseif (($length & 0xF0) === 0xE0) {
                $length = (($length & 0x0F) << 24)
                    + (ord(fread($this->socket, 1)) << 16)
                    + (ord(fread($this->socket, 1)) << 8)
                    + ord(fread($this->socket, 1));
            }
        }

        if ($length === 0) {
            return '';
        }

        return fread($this->socket, $length) ?: '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readResponse(): array
    {
        $results = [];
        $current = [];

        while (($word = $this->readWord()) !== null) {
            if ($word === '') {
                if ($current !== []) {
                    $results[] = $current;
                    $current = [];
                }

                continue;
            }

            if ($word === '!done' || $word === '!trap' || $word === '!fatal') {
                if ($current !== []) {
                    $results[] = $current;
                }

                if ($word === '!trap' || $word === '!fatal') {
                    $current['!type'] = $word;
                    $results[] = $current;
                }

                break;
            }

            if (str_starts_with($word, '=')) {
                $word = substr($word, 1);
                $pos = strpos($word, '=');

                if ($pos !== false) {
                    $key = substr($word, 0, $pos);
                    $value = substr($word, $pos + 1);
                    $current[$key] = $value;
                }
            } else {
                $current['message'] = $word;
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function isTrap(array $response): bool
    {
        return isset($response[0]['!type']) && $response[0]['!type'] === '!trap';
    }
}
