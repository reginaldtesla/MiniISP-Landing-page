<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalize(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '233')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '233'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '233'.$digits;
        }

        return $digits;
    }

    public static function isValid(string $normalized): bool
    {
        return (bool) preg_match('/^233[235]\d{8}$/', $normalized);
    }
}
