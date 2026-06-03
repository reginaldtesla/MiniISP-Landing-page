<?php

namespace App\Models;

use App\Support\PackageUsage;
use App\Support\PackageValidity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'package_slug',
        'package_name',
        'data_limit_mb',
        'data_limit_bytes',
        'bytes_consumed',
        'speed_mbps',
        'validity_type',
        'activated_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'data_limit_mb' => 'integer',
            'data_limit_bytes' => 'integer',
            'bytes_consumed' => 'integer',
            'speed_mbps' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if (PackageValidity::isUnlimited($this)) {
            return true;
        }

        $phone = $this->relationLoaded('user')
            ? $this->user?->phone_number
            : $this->user()->value('phone_number');

        return PackageUsage::hasDataRemaining($this, $phone);
    }

    public function validityLabel(): string
    {
        return PackageValidity::labelForPurchase($this);
    }

    public function hasUnlimitedData(): bool
    {
        return PackageValidity::isUnlimited($this);
    }
}
