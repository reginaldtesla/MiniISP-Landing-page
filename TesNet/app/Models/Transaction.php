<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'package_slug',
        'amount',
        'currency',
        'amount_pesewas',
        'paystack_reference',
        'paystack_access_code',
        'status',
        'channel',
        'metadata',
        'paystack_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'paystack_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function packagePurchase(): HasOne
    {
        return $this->hasOne(PackagePurchase::class);
    }

    public function authorizationUrl(): ?string
    {
        return $this->paystack_response['data']['authorization_url'] ?? null;
    }
}
