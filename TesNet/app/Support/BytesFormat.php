<?php

namespace App\Support;

class BytesFormat
{
    public static function nice(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }

    public static function gbBinary(int $bytes, int $precision = 1): float
    {
        return round($bytes / 1073741824, $precision);
    }

    public static function parseRouterUptimeToSeconds(?string $uptime): ?int
    {
        if ($uptime === null || $uptime === '') {
            return null;
        }

        if (ctype_digit($uptime)) {
            return (int) $uptime;
        }

        $seconds = 0;
        $pattern = '/(\d+)(w|d|h|m|s)/i';

        if (! preg_match_all($pattern, $uptime, $matches, PREG_SET_ORDER)) {
            return null;
        }

        foreach ($matches as $match) {
            $value = (int) $match[1];
            $seconds += match (strtolower($match[2])) {
                'w' => $value * 604800,
                'd' => $value * 86400,
                'h' => $value * 3600,
                'm' => $value * 60,
                default => $value,
            };
        }

        return $seconds > 0 ? $seconds : null;
    }

    public static function formatDurationSeconds(?int $seconds): string
    {
        if ($seconds === null || $seconds < 1) {
            return '—';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        }

        if ($hours > 0) {
            return "{$hours}h {$minutes}m {$secs}s";
        }

        if ($minutes > 0) {
            return "{$minutes}m {$secs}s";
        }

        return "{$secs}s";
    }
}
