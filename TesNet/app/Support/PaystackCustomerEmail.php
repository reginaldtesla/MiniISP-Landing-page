<?php

namespace App\Support;

use App\Models\User;

class PaystackCustomerEmail
{
    /**
     * Paystack rejects addresses like *@tesnet.local. Use phone@configured-domain instead.
     */
    public static function forUser(User $user): string
    {
        return self::forPhone($user->phone_number, $user->id);
    }

    public static function forPhone(?string $phone, ?int $fallbackUserId = null): string
    {
        $normalized = PhoneNumber::normalize($phone);
        $local = $normalized !== '' ? $normalized : 'user'.($fallbackUserId ?? 'guest');
        $domain = config('paystack.customer_email_domain', 'example.com');

        return $local.'@'.$domain;
    }
}
