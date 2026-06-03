<?php

namespace App\Services;

use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;

class MikrotikApiService
{
    protected mixed $socket = null;

    /** @var array<string, array{used: int, limit: int}|null> */
    protected static array $hotspotUsageCache = [];

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

    /**
     * Bytes used / total cap as seen on the router (hotspot user + active sessions).
     *
     * @return array{used: int, limit: int}|null null when API disabled or unreachable
     */
    public function hotspotDataUsageForUser(string $username): ?array
    {
        if (array_key_exists($username, self::$hotspotUsageCache)) {
            return self::$hotspotUsageCache[$username];
        }

        if (! $this->isEnabled()) {
            self::$hotspotUsageCache[$username] = null;

            return null;
        }

        if (! $this->connect()) {
            self::$hotspotUsageCache[$username] = null;

            return null;
        }

        try {
            $best = ['used' => 0, 'limit' => 0];

            foreach ($this->usernameLookupVariants($username) as $variant) {
                $usage = $this->fetchHotspotUsageForVariant($variant);

                if ($usage['used'] > $best['used']) {
                    $best['used'] = $usage['used'];
                }

                if ($usage['limit'] > $best['limit']) {
                    $best['limit'] = $usage['limit'];
                }
            }

            self::$hotspotUsageCache[$username] = $best;

            return $best;
        } catch (\Throwable $exception) {
            Log::warning('MikroTik hotspot usage lookup failed', [
                'username' => $username,
                'error' => $exception->getMessage(),
            ]);
            self::$hotspotUsageCache[$username] = null;

            return null;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Best active hotspot session for live dashboard (matches MikroTik status page fields).
     *
     * @return array{
     *     bytes_in: int,
     *     bytes_out: int,
     *     limit_bytes: int,
     *     uptime: string,
     *     uptime_seconds: int|null,
     *     uptime_label: string
     * }|null
     */
    public function liveActiveSessionForUser(string $username): ?array
    {
        $counters = $this->routerHotspotCounters($username);

        if ($counters === null || ! ($counters['on_active_session'] ?? false)) {
            return null;
        }

        return [
            'bytes_in' => $counters['bytes_in'],
            'bytes_out' => $counters['bytes_out'],
            'limit_bytes' => $counters['limit_bytes'],
            'uptime' => $counters['uptime'] ?? '',
            'uptime_seconds' => $counters['uptime_seconds'],
            'uptime_label' => $counters['uptime_label'],
        ];
    }

    /**
     * Byte counters as stored on the router (hotspot user + active session), same source as MikroTik status.html.
     *
     * @return array{
     *     bytes_in: int,
     *     bytes_out: int,
     *     limit_bytes: int,
     *     on_active_session: bool,
     *     uptime?: string,
     *     uptime_seconds?: int|null,
     *     uptime_label?: string
     * }|null
     */
    public function routerHotspotCounters(string $username): ?array
    {
        if (! $this->isEnabled() || ! $this->connect()) {
            return null;
        }

        try {
            $best = [
                'bytes_in' => 0,
                'bytes_out' => 0,
                'limit_bytes' => 0,
                'on_active_session' => false,
                'uptime' => '',
                'uptime_seconds' => null,
                'uptime_label' => '—',
            ];
            $found = false;

            foreach ($this->usernameLookupVariants($username) as $variant) {
                foreach ($this->command('/ip/hotspot/user/print', ['?name' => $variant]) as $row) {
                    if (isset($row['!type'])) {
                        continue;
                    }

                    $found = true;
                    $best['bytes_in'] = max($best['bytes_in'], (int) ($row['bytes-in'] ?? 0));
                    $best['bytes_out'] = max($best['bytes_out'], (int) ($row['bytes-out'] ?? 0));
                    $best['limit_bytes'] = max($best['limit_bytes'], (int) ($row['limit-bytes-total'] ?? 0));
                }

                foreach ($this->filterHotspotSessions($variant) as $session) {
                    $found = true;
                    $best['on_active_session'] = true;
                    $best['bytes_in'] = max($best['bytes_in'], (int) ($session['bytes-in'] ?? 0));
                    $best['bytes_out'] = max($best['bytes_out'], (int) ($session['bytes-out'] ?? 0));
                    $best['limit_bytes'] = max($best['limit_bytes'], (int) ($session['limit-bytes-total'] ?? 0));

                    $uptime = (string) ($session['uptime'] ?? '');
                    $uptimeSeconds = \App\Support\BytesFormat::parseRouterUptimeToSeconds($uptime);

                    if ($uptime !== '') {
                        $best['uptime'] = $uptime;
                        $best['uptime_seconds'] = $uptimeSeconds;
                        $best['uptime_label'] = \App\Support\BytesFormat::formatDurationSeconds($uptimeSeconds);
                    }
                }
            }

            return $found ? $best : null;
        } catch (\Throwable $exception) {
            Log::warning('MikroTik router counter lookup failed', [
                'username' => $username,
                'error' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            $this->disconnect();
        }
    }

    public function peakActiveSessionBytes(string $username, ?string $macAddress = null, ?string $ipAddress = null): int
    {
        if (! $this->isEnabled() || ! $this->connect()) {
            return 0;
        }

        try {
            $peak = 0;

            foreach ($this->filterHotspotSessions($username, $macAddress, $ipAddress) as $session) {
                $peak = max(
                    $peak,
                    (int) ($session['bytes-in'] ?? 0) + (int) ($session['bytes-out'] ?? 0)
                );
            }

            return $peak;
        } catch (\Throwable $exception) {
            Log::warning('MikroTik active session byte lookup failed', [
                'username' => $username,
                'error' => $exception->getMessage(),
            ]);

            return 0;
        } finally {
            $this->disconnect();
        }
    }

    public function hotspotQuotaExhausted(string $username): bool
    {
        $usage = $this->hotspotDataUsageForUser($username);

        if ($usage === null || $usage['limit'] < 1) {
            return false;
        }

        return $usage['used'] >= $usage['limit'];
    }

    /**
     * Clear stale hotspot byte counters after a new package purchase.
     */
    public function resetHotspotUsageForUser(string $username): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        foreach ($this->usernameLookupVariants($username) as $variant) {
            $this->disconnectHotspotUser($variant);

            if (! $this->connect()) {
                continue;
            }

            try {
                foreach ($this->command('/ip/hotspot/user/print', ['?name' => $variant]) as $row) {
                    if (isset($row['!type']) || ! isset($row['.id'])) {
                        continue;
                    }

                    $this->command('/ip/hotspot/user/remove', ['.id' => $row['.id']]);
                }
            } catch (\Throwable $exception) {
                Log::warning('MikroTik hotspot user reset failed', [
                    'username' => $variant,
                    'error' => $exception->getMessage(),
                ]);
            } finally {
                $this->disconnect();
            }
        }

        self::$hotspotUsageCache = [];
    }

    /**
     * @return array{used: int, limit: int}
     */
    protected function fetchHotspotUsageForVariant(string $username): array
    {
        $used = 0;
        $limit = 0;

        foreach ($this->command('/ip/hotspot/user/print', ['?name' => $username]) as $row) {
            if (isset($row['!type'])) {
                continue;
            }

            $rowUsed = (int) ($row['bytes-in'] ?? 0) + (int) ($row['bytes-out'] ?? 0);
            $used = max($used, $rowUsed);
            $limit = max($limit, (int) ($row['limit-bytes-total'] ?? 0));
        }

        foreach ($this->command('/ip/hotspot/active/print', ['?user' => $username]) as $row) {
            if (isset($row['!type'])) {
                continue;
            }

            $rowUsed = (int) ($row['bytes-in'] ?? 0) + (int) ($row['bytes-out'] ?? 0);
            $used = max($used, $rowUsed);
            $limit = max($limit, (int) ($row['limit-bytes-total'] ?? 0));
        }

        return ['used' => $used, 'limit' => $limit];
    }

    /**
     * @return array<int, string>
     */
    public function upsertHotspotUser(
        string $name,
        string $password,
        string $profile,
        int $limitBytes,
        ?string $comment = null,
        ?int $limitUptimeSeconds = null,
    ): bool {
        if (! $this->isEnabled() || ! $this->connect()) {
            return false;
        }

        try {
            $existingId = null;

            foreach ($this->command('/ip/hotspot/user/print', ['?name' => $name]) as $row) {
                if (isset($row['!type']) || ! isset($row['.id'])) {
                    continue;
                }

                $existingId = $row['.id'];
                break;
            }

            $arguments = [
                'name' => $name,
                'password' => $password,
                'profile' => $profile,
                'disabled' => 'no',
                'comment' => $comment ?? '',
            ];

            if ($limitBytes > 0) {
                $arguments['limit-bytes-total'] = (string) $limitBytes;
            }

            if ($limitUptimeSeconds !== null && $limitUptimeSeconds > 0) {
                $arguments['limit-uptime'] = (string) $limitUptimeSeconds.'s';
            }

            if ($existingId !== null) {
                $arguments['.id'] = $existingId;
                $response = $this->command('/ip/hotspot/user/set', $arguments);
            } else {
                $response = $this->command('/ip/hotspot/user/add', $arguments);
            }

            unset(self::$hotspotUsageCache[$name]);

            return ! $this->responseFailed($response);
        } catch (\Throwable $exception) {
            Log::warning('MikroTik hotspot user upsert failed', [
                'name' => $name,
                'error' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            $this->disconnect();
        }
    }

    public function setHotspotUserDisabled(string $name, bool $disabled = true): bool
    {
        if (! $this->isEnabled() || ! $this->connect()) {
            return false;
        }

        try {
            foreach ($this->command('/ip/hotspot/user/print', ['?name' => $name]) as $row) {
                if (isset($row['!type']) || ! isset($row['.id'])) {
                    continue;
                }

                $response = $this->command('/ip/hotspot/user/set', [
                    '.id' => $row['.id'],
                    'disabled' => $disabled ? 'yes' : 'no',
                ]);

                unset(self::$hotspotUsageCache[$name]);

                return ! $this->responseFailed($response);
            }

            return false;
        } catch (\Throwable $exception) {
            Log::warning('MikroTik hotspot user disable failed', [
                'name' => $name,
                'error' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            $this->disconnect();
        }
    }

    public function removeHotspotUser(string $name): void
    {
        $this->disconnectHotspotUser($name);

        if (! $this->isEnabled() || ! $this->connect()) {
            return;
        }

        try {
            foreach ($this->command('/ip/hotspot/user/print', ['?name' => $name]) as $row) {
                if (isset($row['!type']) || ! isset($row['.id'])) {
                    continue;
                }

                $this->command('/ip/hotspot/user/remove', ['.id' => $row['.id']]);
            }
        } catch (\Throwable $exception) {
            Log::warning('MikroTik hotspot user remove failed', [
                'name' => $name,
                'error' => $exception->getMessage(),
            ]);
        } finally {
            $this->disconnect();
            unset(self::$hotspotUsageCache[$name]);
        }
    }

    protected function usernameLookupVariants(string $username): array
    {
        if (str_starts_with($username, 'tn-')) {
            return [$username];
        }

        $variants = array_filter([$username, PhoneNumber::normalize($username)]);

        foreach ($variants as $variant) {
            if (str_starts_with($variant, '233') && strlen($variant) >= 12) {
                $variants[] = '0'.substr($variant, 3);
            }
        }

        return array_values(array_unique($variants));
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
        $timeout = max(1, (int) config('mikrotik.api.timeout', 2));

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
