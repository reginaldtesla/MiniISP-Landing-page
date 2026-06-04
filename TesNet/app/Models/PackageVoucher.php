<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PackageVoucher extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_REDEEMED = 'redeemed';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'code',
        'package_slug',
        'package_name',
        'amount',
        'amount_pesewas',
        'status',
        'admin_note',
        'created_by',
        'redeemed_by',
        'redeemed_at',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'redeemed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'TES-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public static function normalizeCode(string $input): string
    {
        $input = strtoupper(trim($input));

        if (preg_match('/^TES[- ]?([A-Z0-9]{4})[- ]?([A-Z0-9]{4})$/', $input, $matches)) {
            return 'TES-'.$matches[1].'-'.$matches[2];
        }

        $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $input) ?? '');

        if (strlen($clean) === 11 && str_starts_with($clean, 'TES')) {
            return 'TES-'.substr($clean, 3, 4).'-'.substr($clean, 7, 4);
        }

        return $input;
    }
}
