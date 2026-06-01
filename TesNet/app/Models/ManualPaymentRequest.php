<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualPaymentRequest extends Model
{
    protected $table = 'manual_payment_requests';

    protected $fillable = [
        'user_id',
        'reviewed_by',
        'transaction_id',
        'type',
        'status',
        'package_slug',
        'amount',
        'amount_pesewas',
        'payment_method',
        'provider',
        'payer_phone',
        'reference',
        'note',
        'proof_path',
        'admin_note',
        'reviewed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}

