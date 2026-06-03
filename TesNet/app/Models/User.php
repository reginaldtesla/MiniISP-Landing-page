<?php

namespace App\Models;

use App\Support\PackageUsage;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone_number', 'password', 'device_limit', 'is_admin', 'is_suspended', 'wallet_balance'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static ?string $plainPasswordForRadius = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_suspended' => 'boolean',
            'wallet_balance' => 'decimal:2',
            'device_limit' => 'integer',
        ];
    }

    public static function setPlainPasswordForRadius(?string $password): void
    {
        static::$plainPasswordForRadius = $password;
    }

    public function getPlainPasswordForRadius(): ?string
    {
        return static::$plainPasswordForRadius;
    }

    public function setPhoneNumberAttribute(?string $value): void
    {
        $this->attributes['phone_number'] = PhoneNumber::normalize($value);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function packagePurchases(): HasMany
    {
        return $this->hasMany(PackagePurchase::class);
    }

    public function activePackagePurchases(): HasMany
    {
        return $this->packagePurchases()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function hasActiveDataPlan(): bool
    {
        return PackageUsage::activePurchaseForDisplay($this) !== null;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
